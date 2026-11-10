<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\AcceptBookingInquiry;
use Modules\Bookings\Actions\AddBookingDeposit;
use Modules\Bookings\Actions\CancelBookingInquiry;
use Modules\Bookings\Actions\DeclineBookingInquiry;
use Modules\Bookings\Actions\MarkInquiryContacted;
use Modules\Bookings\Actions\UpdateBookingDeposit;
use Modules\Bookings\Actions\UpdateBookingInquiry;
use Modules\Bookings\Http\Requests\BookingInquiryIndexRequest;
use Modules\Bookings\Http\Requests\StoreBookingDepositRequest;
use Modules\Bookings\Http\Requests\UpdateBookingDepositRequest;
use Modules\Bookings\Http\Requests\UpdateBookingInquiryRequest;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Queries\BookingInquiryQuery;
use Modules\Bookings\Support\BookingActivityPresenter;
use Modules\Bookings\Support\BookingOptionCatalog;
use Modules\Bookings\Support\BookingPresenter;
use Modules\Listings\Models\Listing;
use Spatie\Activitylog\Models\Activity;

class BookingInquiryController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly BookingInquiryQuery $inquiries,
    ) {}

    public function index(BookingInquiryIndexRequest $request): Response
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [BookingInquiry::class, $team]);

        $filters = $request->validated();
        $activeListing = $filters['listing'] ?? null
            ? Listing::query()
                ->where('team_id', $team->id)
                ->where('slug', $filters['listing'])
                ->first(['id', 'name', 'slug'])
            : null;

        return $this->renderIndex($team, $filters, $activeListing);
    }

    public function listing(
        BookingInquiryIndexRequest $request,
        string $currentTeam,
        Listing $listing,
    ): Response {
        $team = $this->currentTeam($request);
        $this->ensureOwnedListing($listing, $team->id);

        Gate::authorize('viewAny', [BookingInquiry::class, $team]);

        $filters = $request->validated();
        $filters['listing'] = $listing->slug;

        return $this->renderIndex($team, $filters, $listing);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function renderIndex(
        Team $team,
        array $filters,
        ?Listing $activeListing,
    ): Response {
        $inquiries = $this->inquiries->paginate($team, $filters);
        $listings = $this->inquiries->listings($team);

        return Inertia::render('Bookings/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'inquiries' => $inquiries,
            'listings' => $listings,
            'filters' => $filters,
            'statuses' => BookingOptionCatalog::inquiryStatuses(),
            'canUseCustomers' => $this->entitlements->allows($team, 'customers'),
            'canCreateBooking' => Gate::allows('create', [BookingInquiry::class, $team]),
            'activeListing' => $activeListing,
            'management' => $activeListing ? [
                'manage' => route('listings.manage', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'edit' => route('listings.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'bookings' => route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'customers' => route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'availability' => route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
                'listings' => route('listings.index', ['current_team' => $team->slug]),
            ] : null,
            'urls' => [
                'index' => $activeListing
                    ? route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.index', ['current_team' => $team->slug]),
                'create' => $activeListing
                    ? route('bookings.listing.create', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.create', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'today' => route('bookings.today', ['current_team' => $team->slug]),
                'quotes' => route('bookings.quotes.index', ['current_team' => $team->slug]),
                'reports' => route('bookings.reports', ['current_team' => $team->slug]),
                'billing' => route('billing.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function show(Request $request, string $currentTeam, BookingInquiry $inquiry): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('view', $inquiry);

        $inquiry->load(['listing', 'customer', 'deposits', 'contract.bookingInquiry']);

        $activities = Activity::query()
            ->with('causer')
            ->where('log_name', 'bookings')
            ->where('subject_type', BookingInquiry::class)
            ->where('subject_id', $inquiry->id)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Activity $activity): array => BookingActivityPresenter::present($activity))
            ->all();

        return Inertia::render('Bookings/Show', [
            'inquiry' => BookingPresenter::inquiryDetail($inquiry, $team->slug),
            'activities' => $activities,
            'statuses' => BookingOptionCatalog::inquiryStatuses(),
            'depositStatuses' => BookingOptionCatalog::depositStatuses(),
            'depositMethods' => BookingOptionCatalog::depositMethods(),
            'canManage' => Gate::allows('accept', $inquiry),
            'canEdit' => Gate::allows('edit', $inquiry),
            'canManageDeposits' => Gate::allows('manageDeposits', $inquiry),
            'urls' => [
                'index' => route('bookings.listing', [
                    'current_team' => $team->slug,
                    'listing' => $inquiry->listing->slug,
                ]),
                'edit' => route('bookings.inquiries.edit', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'update' => route('bookings.inquiries.update', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'contacted' => route('bookings.inquiries.contacted', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'accept' => route('bookings.inquiries.accept', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'decline' => route('bookings.inquiries.decline', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'cancel' => route('bookings.inquiries.cancel', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'deposits_store' => route('bookings.deposits.store', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'contract_store' => route('bookings.contracts.store', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
            ],
        ]);
    }

    public function edit(Request $request, string $currentTeam, BookingInquiry $inquiry): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('edit', $inquiry);

        $inquiry->load(['listing', 'customer']);

        return Inertia::render('Bookings/Edit', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'inquiry' => BookingPresenter::inquiryDetail($inquiry, $team->slug),
            'urls' => [
                'show' => route('bookings.show', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
                'update' => route('bookings.inquiries.update', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
            ],
        ]);
    }

    public function update(
        UpdateBookingInquiryRequest $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        UpdateBookingInquiry $updateInquiry,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('edit', $inquiry);

        $inquiry = $updateInquiry->handle(
            $inquiry,
            $request->user(),
            $request->validated(),
            $request->boolean('notify_customer', true),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reserva actualizada.',
        ]);

        return to_route('bookings.show', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]);
    }

    public function contacted(
        Request $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        MarkInquiryContacted $markContacted,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('update', $inquiry);

        $markContacted->handle($inquiry, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud marcada como contactada.']);

        return back();
    }

    public function accept(
        Request $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        AcceptBookingInquiry $acceptInquiry,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('accept', $inquiry);

        $acceptInquiry->handle($inquiry, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud aceptada y fechas reservadas.']);

        return back();
    }

    public function decline(
        Request $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        DeclineBookingInquiry $declineInquiry,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('decline', $inquiry);

        $declineInquiry->handle($inquiry, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud rechazada.']);

        return back();
    }

    public function cancel(
        Request $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        CancelBookingInquiry $cancelInquiry,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('cancel', $inquiry);

        $cancelInquiry->handle($inquiry, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud cancelada y fechas liberadas.']);

        return back();
    }

    public function storeDeposit(
        StoreBookingDepositRequest $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        AddBookingDeposit $addDeposit,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        Gate::authorize('manageDeposits', $inquiry);

        $addDeposit->handle($inquiry, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anticipo registrado.']);

        return back();
    }

    public function updateDeposit(
        UpdateBookingDepositRequest $request,
        string $currentTeam,
        BookingInquiry $inquiry,
        BookingDeposit $deposit,
        UpdateBookingDeposit $updateDeposit,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($inquiry, $team->id);

        abort_unless($deposit->booking_inquiry_id === $inquiry->id, 404);

        Gate::authorize('manageDeposits', $inquiry);

        $updateDeposit->handle($deposit, $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anticipo actualizado.']);

        return back();
    }

    private function ensureOwned(BookingInquiry $inquiry, int $teamId): void
    {
        abort_unless($inquiry->team_id === $teamId, 404);
    }

    private function ensureOwnedListing(Listing $listing, int $teamId): void
    {
        abort_unless($listing->team_id === $teamId, 404);
    }
}
