<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\Bookings\Actions\CreateAvailabilityBlockSeries;
use Modules\Bookings\Actions\DeleteAvailabilityBlockSeries;
use Modules\Bookings\Http\Requests\StoreAvailabilityBlockSeriesRequest;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Listings\Models\Listing;

class AvailabilityBlockSeriesController extends Controller
{
    use ResolvesCurrentTeam;

    public function store(
        StoreAvailabilityBlockSeriesRequest $request,
        string $currentTeam,
        Listing $listing,
        CreateAvailabilityBlockSeries $createSeries,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        Gate::authorize('update', $listing);

        $result = $createSeries->handle($listing, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['created'].' fechas bloqueadas.'
                .($result['skipped'] === [] ? '' : ' '.count($result['skipped']).' omitidas por conflicto.'),
        ]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        AvailabilityBlockSeries $series,
        DeleteAvailabilityBlockSeries $deleteSeries,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team->id);

        abort_unless(
            $series->listing_id === $listing->id && $series->team_id === $team->id,
            404,
        );

        Gate::authorize('update', $listing);

        $deleted = $deleteSeries->handle($series, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $deleted === 1
                ? 'Serie eliminada y fecha futura liberada.'
                : "Serie eliminada y {$deleted} fechas futuras liberadas.",
        ]);

        return back();
    }

    private function ensureOwned(Listing $listing, int $teamId): void
    {
        abort_unless($listing->team_id === $teamId, 404);
    }
}
