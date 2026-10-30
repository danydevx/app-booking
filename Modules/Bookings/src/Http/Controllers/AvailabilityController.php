<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\BlockListingDate;
use Modules\Bookings\Actions\UnblockListingDate;
use Modules\Bookings\Http\Requests\BlockListingDateRequest;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Bookings\Support\ResolvesCurrentTeam;
use Modules\Listings\Models\Listing;

class AvailabilityController extends Controller
{
    use ResolvesCurrentTeam;

    public function edit(
        Request $request,
        string $currentTeam,
        Listing $listing,
        AvailabilityService $availability,
    ): Response {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $today = CarbonImmutable::now($listing->timezone)->toDateString();
        $end = CarbonImmutable::now($listing->timezone)->addDays(365)->toDateString();

        return Inertia::render('Bookings/Availability/Edit', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'status' => $listing->status->value,
                'status_label' => $listing->status->label(),
                'timezone' => $listing->timezone,
            ],
            'blocked_dates' => $availability->blockedDates($listing, $today, $end),
            'urls' => [
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'block' => route('bookings.availability.block', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
                'unblock' => route('bookings.availability.unblock', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'date' => '__DATE__',
                ]),
                'public_listing' => route('public.listings.show', ['listing' => $listing->slug]),
            ],
        ]);
    }

    public function block(
        BlockListingDateRequest $request,
        string $currentTeam,
        Listing $listing,
        BlockListingDate $blockDate,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $blockDate->handle(
            $listing,
            (string) $request->validated('date'),
            $request->validated('reason'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fecha bloqueada.']);

        return back();
    }

    public function unblock(
        Request $request,
        string $currentTeam,
        Listing $listing,
        string $date,
        UnblockListingDate $unblockDate,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $unblockDate->handle($listing, $date);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fecha liberada.']);

        return back();
    }

    private function ensureOwned(Listing $listing, int $teamId): void
    {
        abort_unless($listing->team_id === $teamId, 404);
    }
}
