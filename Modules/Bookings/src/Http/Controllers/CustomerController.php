<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\CreateCustomer;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Http\Requests\StoreCustomerRequest;
use Modules\Bookings\Http\Requests\UpdateCustomerRequest;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerTag;
use Modules\Bookings\Models\CustomerTask;
use Modules\Bookings\Models\EmailTemplate;
use Modules\Bookings\Support\BookingPresenter;
use Modules\Bookings\Support\CustomerActivityPresenter;
use Modules\Bookings\Support\CustomerCsvExport;
use Modules\Bookings\Support\CustomerMetrics;
use Modules\Bookings\Support\CustomerQuotePresenter;
use Modules\Bookings\Support\CustomerTaskPresenter;
use Modules\Listings\Models\Listing;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly RecordCustomerActivity $activityLogger,
        private readonly CustomerMetrics $metrics,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        return $this->renderIndex($request);
    }

    public function forListing(
        Request $request,
        string $currentTeam,
        Listing $listing,
    ): Response|RedirectResponse {
        $team = $this->currentTeam($request);
        abort_unless($listing->team_id === $team->id, 404);

        return $this->renderIndex($request, $listing);
    }

    private function renderIndex(
        Request $request,
        ?Listing $activeListing = null,
    ): Response|RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $search = $request->string('search')->toString();
        $tag = $request->string('tag')->toString();
        $stage = $request->string('stage')->toString();
        $listingFilter = $activeListing === null
            ? $request->string('listing')->toString()
            : $activeListing->slug;
        $archived = $request->boolean('archived');
        $assigned = $request->string('assigned')->toString();
        $currentUserId = (int) $request->user()->id;

        if ($activeListing === null && $listingFilter !== '') {
            $activeListing = Listing::query()
                ->where('team_id', $team->id)
                ->where('slug', $listingFilter)
                ->first(['id', 'name', 'slug']);
        }

        $scopeInquiries = fn ($query) => $query->when(
            $activeListing,
            fn ($scoped) => $scoped->where('listing_id', $activeListing->id),
        );

        $customers = Customer::query()
            ->where('team_id', $team->id)
            ->with(['tags', 'assignee'])
            ->when(
                $archived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($stage !== '', fn ($query) => $query->where('pipeline_stage', $stage))
            ->when($assigned !== '', function ($query) use ($assigned, $currentUserId): void {
                if ($assigned === 'me') {
                    $query->where('assigned_to', $currentUserId);

                    return;
                }

                if ($assigned === 'none') {
                    $query->whereNull('assigned_to');

                    return;
                }

                $query->where('assigned_to', (int) $assigned);
            })
            ->when($listingFilter !== '', fn ($query) => $query->where(
                fn ($scoped) => $scoped
                    ->where('listing_id', $activeListing === null ? 0 : $activeListing->id)
                    ->orWhereHas(
                        'inquiries',
                        fn ($inquiryQuery) => $inquiryQuery->whereHas(
                            'listing',
                            fn ($listingQuery) => $listingQuery->where('slug', $listingFilter),
                        ),
                    ),
            ))
            ->when($tag !== '', fn ($query) => $query->whereHas(
                'tags',
                fn ($tagQuery) => $tagQuery->where('slug', $tag),
            ))
            ->withCount([
                'inquiries' => $scopeInquiries,
                'inquiries as accepted_inquiries_count' => fn ($query) => $scopeInquiries($query)->where('status', 'accepted'),
                'tasks as open_tasks_count' => fn ($query) => $query->where('status', 'open'),
                'tasks as overdue_tasks_count' => fn ($query) => $query->where('status', 'open')->where('due_at', '<', now()),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) config('bookings.per_page', 15))
            ->withQueryString();

        $customers->through(
            fn (Customer $customer): array => BookingPresenter::customerCard(
                $customer,
                $team->slug,
                $activeListing?->slug,
            ),
        );

        $tags = CustomerTag::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerTag $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'color' => $item->color,
            ])
            ->all();

        $listingOptions = Listing::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Listing $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])
            ->all();

        $teamMembers = $team->members()
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
            ])
            ->all();

        $management = $activeListing ? [
            'manage' => route('listings.manage', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'edit' => route('listings.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'bookings' => route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'customers' => route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'availability' => route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'listings' => route('listings.index', ['current_team' => $team->slug]),
        ] : null;

        return Inertia::render('Bookings/Customers/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'customers' => $customers,
            'tags' => $tags,
            'listings' => $listingOptions,
            'teamMembers' => $teamMembers,
            'activeListing' => $activeListing,
            'management' => $management,
            'pipelineStages' => CustomerPipelineStage::options(),
            'archived' => $archived,
            'filters' => [
                'search' => $search,
                'tag' => $tag,
                'stage' => $stage,
                'listing' => $listingFilter,
                'assigned' => $assigned,
            ],
            'urls' => [
                'bookings' => $activeListing
                    ? route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.index', ['current_team' => $team->slug]),
                'index' => $activeListing
                    ? route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.customers.index', ['current_team' => $team->slug]),
                'store' => $activeListing
                    ? route('bookings.listing.customers.store', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.customers.store', ['current_team' => $team->slug]),
                'export' => route('bookings.customers.export', [
                    'current_team' => $team->slug,
                    ...($activeListing ? ['listing' => $activeListing->slug] : []),
                ]),
                'import' => route('bookings.customers.import', [
                    'current_team' => $team->slug,
                    ...($activeListing ? ['listing' => $activeListing->slug] : []),
                ]),
            ],
        ]);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomer $createCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        return $this->storeFor($request, $team, $createCustomer);
    }

    public function storeForListing(
        StoreCustomerRequest $request,
        string $currentTeam,
        Listing $listing,
        CreateCustomer $createCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        abort_unless($listing->team_id === $team->id, 404);

        return $this->storeFor($request, $team, $createCustomer, $listing);
    }

    private function storeFor(
        StoreCustomerRequest $request,
        Team $team,
        CreateCustomer $createCustomer,
        ?Listing $activeListing = null,
    ): RedirectResponse {
        Gate::authorize('create', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $data = $request->validated();
        $data['assigned_to'] ??= $request->user()->id;

        $customer = $createCustomer->handle($team, $data, $activeListing);

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            'Cliente creado manualmente.',
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return $activeListing
            ? to_route('bookings.listing.customers.show', [
                'current_team' => $team->slug,
                'listing' => $activeListing->slug,
                'customer' => $customer->id,
            ])
            : to_route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]);
    }

    public function export(
        Request $request,
        CustomerCsvExport $export,
    ): StreamedResponse|RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $listingSlug = trim($request->string('listing')->toString());
        $listing = $listingSlug !== ''
            ? Listing::query()
                ->where('team_id', $team->id)
                ->where('slug', $listingSlug)
                ->first()
            : null;

        if ($listingSlug !== '' && $listing === null) {
            abort(404);
        }

        return $export->stream(
            $team,
            trim($request->string('search')->toString()),
            $listing,
        );
    }

    public function show(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'summary');
    }

    public function tasks(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'tasks');
    }

    public function timeline(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'timeline');
    }

    public function emails(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'emails');
    }

    public function quotes(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'quotes');
    }

    public function inquiries(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'inquiries');
    }

    public function data(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'data');
    }

    public function tags(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'tags');
    }

    public function lifecycle(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, null, 'lifecycle');
    }

    public function showForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'summary');
    }

    public function tasksForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'tasks');
    }

    public function timelineForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'timeline');
    }

    public function emailsForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'emails');
    }

    public function quotesForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'quotes');
    }

    public function inquiriesForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'inquiries');
    }

    public function dataForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'data');
    }

    public function tagsForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'tags');
    }

    public function lifecycleForListing(Request $request, string $currentTeam, Listing $listing, Customer $customer): Response|RedirectResponse
    {
        return $this->renderShow($request, $currentTeam, $customer, $listing, 'lifecycle');
    }

    private function renderShow(
        Request $request,
        string $currentTeam,
        Customer $customer,
        ?Listing $activeListing = null,
        string $section = 'summary',
    ): Response|RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        if ($activeListing !== null) {
            abort_unless($activeListing->team_id === $team->id, 404);

            $belongsToListing = $customer->listing_id === $activeListing->id
                || $customer->inquiries()
                    ->where('listing_id', $activeListing->id)
                    ->exists();

            abort_unless($belongsToListing, 404);
        }

        Gate::authorize('view', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->load([
            'assignee',
            'tags',
            'inquiries' => function ($query) use ($activeListing): void {
                $query->with(['listing', 'deposits'])
                    ->when($activeListing, fn ($inquiryQuery) => $inquiryQuery->where('listing_id', $activeListing->id));
            },
        ]);
        $customer->loadCount('inquiries');

        $canManageTags = Gate::allows('create', [CustomerTag::class, $team]);
        $tags = CustomerTag::query()
            ->where('team_id', $team->id)
            ->withCount('customers')
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerTag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'customers_count' => (int) $tag->customers_count,
                'delete_url' => $canManageTags
                    ? route('bookings.customers.tags.destroy', ['current_team' => $team->slug, 'tag' => $tag->id])
                    : null,
            ])
            ->all();

        $tasks = $customer->tasks()
            ->with(['assignee', 'team'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->latest('id')
            ->get()
            ->map(fn (CustomerTask $task): array => CustomerTaskPresenter::present(
                $task,
                Gate::allows('update', $task),
            ))
            ->all();

        $teamMembers = $team->members()
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
            ])
            ->all();

        $metrics = $this->metrics->for($customer, $activeListing);

        $mergeCandidates = Customer::query()
            ->where('team_id', $team->id)
            ->whereKeyNot($customer->id)
            ->whereNull('archived_at')
            ->whereNull('merged_into_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (Customer $candidate): array => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
            ])
            ->all();

        $phones = array_values(array_filter([
            $customer->phone,
            $customer->whatsapp_phone,
        ]));

        $duplicateCandidates = $phones === []
            ? []
            : Customer::query()
                ->where('team_id', $team->id)
                ->whereKeyNot($customer->id)
                ->whereNull('archived_at')
                ->whereNull('merged_into_id')
                ->where(function ($query) use ($phones): void {
                    foreach ($phones as $phone) {
                        $query->orWhere('phone', $phone)
                            ->orWhere('whatsapp_phone', $phone);
                    }
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'email', 'phone'])
                ->map(fn (Customer $candidate): array => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'email' => $candidate->email,
                    'phone' => $candidate->phone,
                    'show_url' => $activeListing
                        ? route('bookings.listing.customers.show', [
                            'current_team' => $team->slug,
                            'listing' => $activeListing->slug,
                            'customer' => $candidate->id,
                        ])
                        : route('bookings.customers.show', [
                            'current_team' => $team->slug,
                            'customer' => $candidate->id,
                        ]),
                ])
                ->all();

        $activities = $customer->activities()
            ->with(['user', 'team'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (CustomerActivity $activity): array => CustomerActivityPresenter::present(
                $activity,
                Gate::allows('delete', $activity),
            ))
            ->all();

        $quotes = CustomerQuote::query()
            ->with(['customer', 'listing'])
            ->where('team_id', $team->id)
            ->where('customer_id', $customer->id)
            ->when($activeListing, fn ($query) => $query->where('listing_id', $activeListing->id))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CustomerQuote $quote): array => CustomerQuotePresenter::present($quote, $team->slug))
            ->all();

        $quoteListings = Listing::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Listing $listing): array => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
            ])
            ->all();

        $emailTemplates = EmailTemplate::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'body'])
            ->map(fn (EmailTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
            ])
            ->all();

        $sectionUrls = [];
        $sections = [
            'summary' => 'show',
            'tasks' => 'tasks',
            'timeline' => 'timeline',
            'emails' => 'emails',
            'quotes' => 'quotes',
            'inquiries' => 'inquiries',
            'data' => 'data',
            'tags' => 'tags',
            'lifecycle' => 'lifecycle',
        ];

        foreach ($sections as $key => $routeName) {
            $sectionUrls[$key] = $activeListing
                ? route("bookings.listing.customers.{$routeName}", [
                    'current_team' => $team->slug,
                    'listing' => $activeListing->slug,
                    'customer' => $customer->id,
                ])
                : route("bookings.customers.{$routeName}", [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]);
        }

        return Inertia::render('Bookings/Customers/Show', [
            'customer' => [
                ...BookingPresenter::customerCard($customer, $team->slug, $activeListing?->slug),
                'inquiries' => $customer->inquiries
                    ->map(fn (BookingInquiry $inquiry): array => BookingPresenter::inquiryCard($inquiry, $team->slug))
                    ->all(),
            ],
            'activities' => $activities,
            'activityTypes' => CustomerActivityType::userOptions(),
            'metrics' => [
                'total_inquiries' => $metrics['total_inquiries'],
                'accepted_inquiries' => $metrics['accepted_inquiries'],
                'upcoming_bookings' => $metrics['upcoming_bookings'],
                'deposits_received_cents' => $metrics['deposits_received_cents'],
                'last_activity_at' => $metrics['last_activity_at'],
                'next_task' => $metrics['next_task']
                    ? [
                        'id' => $metrics['next_task']->id,
                        'title' => $metrics['next_task']->title,
                        'due_at' => $metrics['next_task']->due_at?->toIso8601String(),
                        'priority_label' => $metrics['next_task']->priority->label(),
                    ]
                    : null,
            ],
            'tasks' => $tasks,
            'taskPriorities' => CustomerTaskPriority::options(),
            'teamMembers' => $teamMembers,
            'tags' => $tags,
            'canManageTags' => $canManageTags,
            'quotes' => $quotes,
            'quoteListings' => $quoteListings,
            'emailTemplates' => $emailTemplates,
            'section' => $section,
            'sectionUrls' => $sectionUrls,
            'activeListing' => $activeListing,
            'management' => $activeListing ? [
                'manage' => route('listings.manage', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'edit' => route('listings.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'bookings' => route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'customers' => route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'availability' => route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'listings' => route('listings.index', ['current_team' => $team->slug]),
            ] : null,
            'pipelineStages' => CustomerPipelineStage::options(),
            'isArchived' => $customer->archived_at !== null,
            'isAnonymized' => $customer->anonymized_at !== null,
            'canManageLifecycle' => Gate::allows('archive', $customer),
            'canMerge' => Gate::allows('merge', $customer),
            'canAnonymize' => Gate::allows('anonymize', $customer),
            'mergeCandidates' => $mergeCandidates,
            'duplicateCandidates' => $duplicateCandidates,
            'urls' => [
                'index' => $activeListing
                    ? route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.customers.index', ['current_team' => $team->slug]),
                'bookings' => $activeListing
                    ? route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.index', ['current_team' => $team->slug]),
                'update' => route('bookings.customers.update', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'activities_store' => route('bookings.customers.activities.store', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'tasks_store' => route('bookings.customers.tasks.store', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'tags_store' => route('bookings.customers.tags.store', ['current_team' => $team->slug]),
                'quotes_store' => route('bookings.customers.quotes.store', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'quotes_index' => route('bookings.quotes.index', ['current_team' => $team->slug]),
                'emails_send' => route('bookings.customers.emails.send', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'email_opt_in' => route('bookings.customers.email-opt-in', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'tags_sync' => route('bookings.customers.tags.sync', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'pipeline_update' => route('bookings.customers.pipeline.update', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'merge' => route('bookings.customers.merge', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'archive' => route('bookings.customers.archive', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'restore' => route('bookings.customers.restore', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
                'anonymize' => route('bookings.customers.anonymize', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
            ],
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        string $currentTeam,
        Customer $customer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->update($request->validated());

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            'Datos del cliente actualizados.',
            $request->user(),
            null,
            ['changed' => array_keys($customer->getChanges())],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado.']);

        return back();
    }

    private function ensureOwned(Customer $customer, int $teamId): void
    {
        abort_unless($customer->team_id === $teamId, 404);
    }

    private function upgradeRedirect(Team $team): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'El CRM de clientes está disponible en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }
}
