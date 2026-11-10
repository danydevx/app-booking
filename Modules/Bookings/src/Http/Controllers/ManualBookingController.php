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
use Modules\Bookings\Actions\CreateManualBooking;
use Modules\Bookings\Http\Requests\StoreManualBookingRequest;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Listings\Models\Listing;

class ManualBookingController extends Controller
{
    use ResolvesCurrentTeam;

    public function create(Request $request): Response
    {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [BookingInquiry::class, $team]);

        return $this->renderCreate($team);
    }

    public function createForListing(
        Request $request,
        string $currentTeam,
        Listing $listing,
    ): Response {
        $team = $this->currentTeam($request);
        $this->ensureOwnedListing($listing, $team->id);

        Gate::authorize('create', [BookingInquiry::class, $team]);

        return $this->renderCreate($team, $listing);
    }

    public function store(
        StoreManualBookingRequest $request,
        CreateManualBooking $createBooking,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [BookingInquiry::class, $team]);

        $listing = Listing::query()
            ->where('team_id', $team->id)
            ->whereNull('suspended_at')
            ->findOrFail($request->integer('listing_id'));

        return $this->storeFor($request, $team->slug, $listing, $createBooking);
    }

    public function storeForListing(
        StoreManualBookingRequest $request,
        string $currentTeam,
        Listing $listing,
        CreateManualBooking $createBooking,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedListing($listing, $team->id);

        Gate::authorize('create', [BookingInquiry::class, $team]);

        $listing = Listing::query()
            ->where('team_id', $team->id)
            ->whereNull('suspended_at')
            ->findOrFail($listing->id);

        return $this->storeFor($request, $team->slug, $listing, $createBooking);
    }

    private function renderCreate(Team $team, ?Listing $activeListing = null): Response
    {
        $listings = Listing::query()
            ->where('team_id', $team->id)
            ->whereNull('suspended_at')
            ->when($activeListing, fn ($query) => $query->whereKey($activeListing->id))
            ->orderBy('name')
            ->get()
            ->map(fn (Listing $listing): array => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'timezone' => $listing->timezone,
                'capacity' => $listing->capacity,
            ])
            ->values()
            ->all();

        $management = $activeListing ? [
            'manage' => route('listings.manage', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'edit' => route('listings.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'bookings' => route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'customers' => route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'availability' => route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $activeListing->slug]),
            'listings' => route('listings.index', ['current_team' => $team->slug]),
        ] : null;

        return Inertia::render('Bookings/Create', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'listings' => $listings,
            'activeListing' => $activeListing,
            'management' => $management,
            'urls' => [
                'index' => $activeListing
                    ? route('bookings.listing', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.index', ['current_team' => $team->slug]),
                'store' => $activeListing
                    ? route('bookings.listing.create.store', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.create.store', ['current_team' => $team->slug]),
            ],
        ]);
    }

    private function storeFor(
        StoreManualBookingRequest $request,
        string $teamSlug,
        Listing $listing,
        CreateManualBooking $createBooking,
    ): RedirectResponse {
        $inquiry = $createBooking->handle(
            $listing,
            $request->user(),
            $request->validated(),
            $request->boolean('notify_customer', true),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reserva manual registrada y fechas bloqueadas.',
        ]);

        return to_route('bookings.show', [
            'current_team' => $teamSlug,
            'inquiry' => $inquiry->id,
        ]);
    }

    private function ensureOwnedListing(Listing $listing, int $teamId): void
    {
        abort_unless($listing->team_id === $teamId, 404);
    }
}
