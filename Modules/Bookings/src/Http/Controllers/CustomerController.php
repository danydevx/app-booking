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
use Modules\Bookings\Models\CustomerTag;
use Modules\Bookings\Models\CustomerTask;
use Modules\Bookings\Support\BookingPresenter;
use Modules\Bookings\Support\CustomerActivityPresenter;
use Modules\Bookings\Support\CustomerCsvExport;
use Modules\Bookings\Support\CustomerMetrics;
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
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $search = $request->string('search')->toString();
        $tag = $request->string('tag')->toString();
        $stage = $request->string('stage')->toString();
        $listingFilter = $request->string('listing')->toString();
        $archived = $request->boolean('archived');

        $customers = Customer::query()
            ->where('team_id', $team->id)
            ->with('tags')
            ->when(
                $archived,
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($stage !== '', fn ($query) => $query->where('pipeline_stage', $stage))
            ->when($listingFilter !== '', fn ($query) => $query->whereHas(
                'inquiries',
                fn ($inquiryQuery) => $inquiryQuery->whereHas(
                    'listing',
                    fn ($listingQuery) => $listingQuery->where('slug', $listingFilter),
                ),
            ))
            ->when($tag !== '', fn ($query) => $query->whereHas(
                'tags',
                fn ($tagQuery) => $tagQuery->where('slug', $tag),
            ))
            ->withCount([
                'inquiries',
                'inquiries as accepted_inquiries_count' => fn ($query) => $query->where('status', 'accepted'),
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
            fn (Customer $customer): array => BookingPresenter::customerCard($customer, $team->slug),
        );

        $tags = CustomerTag::query()
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerTag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
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

        return Inertia::render('Bookings/Customers/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'customers' => $customers,
            'tags' => $tags,
            'listings' => $listingOptions,
            'pipelineStages' => CustomerPipelineStage::options(),
            'archived' => $archived,
            'filters' => [
                'search' => $search,
                'tag' => $tag,
                'stage' => $stage,
                'listing' => $listingFilter,
            ],
            'urls' => [
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'index' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'store' => route('bookings.customers.store', ['current_team' => $team->slug]),
                'export' => route('bookings.customers.export', ['current_team' => $team->slug]),
                'import' => route('bookings.customers.import', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomer $createCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer = $createCustomer->handle($team, $request->validated());

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            'Cliente creado manualmente.',
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return to_route('bookings.customers.show', [
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

        return $export->stream(
            $team,
            trim($request->string('search')->toString()),
        );
    }

    public function show(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('view', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->load(['inquiries.listing', 'inquiries.deposits', 'tags']);
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

        $metrics = $this->metrics->for($customer);

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

        return Inertia::render('Bookings/Customers/Show', [
            'customer' => [
                ...BookingPresenter::customerCard($customer, $team->slug),
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
            'pipelineStages' => CustomerPipelineStage::options(),
            'isArchived' => $customer->archived_at !== null,
            'isAnonymized' => $customer->anonymized_at !== null,
            'canManageLifecycle' => Gate::allows('archive', $customer),
            'canMerge' => Gate::allows('merge', $customer),
            'canAnonymize' => Gate::allows('anonymize', $customer),
            'mergeCandidates' => $mergeCandidates,
            'urls' => [
                'index' => route('bookings.customers.index', ['current_team' => $team->slug]),
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
