<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\BlockListingDate;
use Modules\Bookings\Actions\BlockListingDateRange;
use Modules\Bookings\Actions\UnblockListingDate;
use Modules\Bookings\Actions\UnblockListingDateRange;
use Modules\Bookings\Enums\AvailabilityRecurrence;
use Modules\Bookings\Http\Requests\BlockListingDateRangeRequest;
use Modules\Bookings\Http\Requests\BlockListingDateRequest;
use Modules\Bookings\Http\Requests\UnblockListingDateRangeRequest;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Bookings\Support\AvailabilityService;
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

        $series = AvailabilityBlockSeries::query()
            ->where('team_id', $team->id)
            ->where('listing_id', $listing->id)
            ->withCount('unavailabilities')
            ->orderBy('starts_on')
            ->get()
            ->map(fn (AvailabilityBlockSeries $item): array => [
                'id' => $item->id,
                'reason' => $item->reason,
                'recurrence' => $item->recurrence->value,
                'recurrence_label' => $item->recurrence->label(),
                'weekdays' => $item->weekdays ?? [],
                'starts_on' => $item->starts_on->toDateString(),
                'ends_on' => $item->ends_on->toDateString(),
                'unavailabilities_count' => (int) $item->unavailabilities_count,
                'destroy_url' => route('bookings.availability.series.destroy', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'series' => $item->id,
                ]),
            ])
            ->all();

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
            'series' => $series,
            'recurrences' => AvailabilityRecurrence::options(),
            'horizon_days' => (int) config('bookings.availability_recurrence_horizon_days', 366),
            'urls' => [
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'block_range' => route('bookings.availability.block-range', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
                'unblock' => route('bookings.availability.unblock', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'date' => '__DATE__',
                ]),
                'unblock_range' => route('bookings.availability.unblock-range', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
                'series_store' => route('bookings.availability.series.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
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

    public function blockRange(
        BlockListingDateRangeRequest $request,
        string $currentTeam,
        Listing $listing,
        BlockListingDateRange $blockRange,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $count = $blockRange->handle(
            $listing,
            (string) $request->validated('start_date'),
            (string) $request->validated('end_date'),
            $request->validated('reason'),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count === 1 ? 'Fecha bloqueada.' : "{$count} fechas bloqueadas.",
        ]);

        return back();
    }

    public function unblockRange(
        UnblockListingDateRangeRequest $request,
        string $currentTeam,
        Listing $listing,
        UnblockListingDateRange $unblockRange,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $count = $unblockRange->handle(
            $listing,
            (string) $request->validated('start_date'),
            (string) $request->validated('end_date'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count === 0
                ? 'No había fechas bloqueadas en ese rango.'
                : ($count === 1 ? 'Fecha liberada.' : "{$count} fechas liberadas."),
        ]);

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
