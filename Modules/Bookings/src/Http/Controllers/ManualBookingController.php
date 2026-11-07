<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
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

        $listings = Listing::query()
            ->where('team_id', $team->id)
            ->whereNull('suspended_at')
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

        return Inertia::render('Bookings/Create', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'listings' => $listings,
            'urls' => [
                'index' => route('bookings.index', ['current_team' => $team->slug]),
                'store' => route('bookings.create.store', ['current_team' => $team->slug]),
            ],
        ]);
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

        $inquiry = $createBooking->handle($listing, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reserva manual registrada y fechas bloqueadas.',
        ]);

        return to_route('bookings.show', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]);
    }
}
