<?php

namespace Modules\Listings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\Listings\Http\Requests\SaveListingPriceRangeRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPriceRange;

class ListingPriceRangeController extends Controller
{
    use ResolvesCurrentTeam;

    public function store(
        SaveListingPriceRangeRequest $request,
        string $currentTeam,
        Listing $listing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $range = new ListingPriceRange($this->payload($request->validated()));
        $range->team_id = $team->id;
        $range->listing_id = $listing->id;
        $range->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarifa por temporada creada.']);

        return back();
    }

    public function update(
        SaveListingPriceRangeRequest $request,
        string $currentTeam,
        Listing $listing,
        ListingPriceRange $range,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensureRangeBelongs($range, $listing, $team);

        Gate::authorize('update', $listing);

        $range->update($this->payload($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarifa actualizada.']);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        ListingPriceRange $range,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensureRangeBelongs($range, $listing, $team);

        Gate::authorize('update', $listing);

        $range->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarifa eliminada.']);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'price_cents' => (int) round(((float) $data['price']) * 100),
            'included_hours' => $data['included_hours'] ?? null,
            'included_people' => $data['included_people'] ?? null,
            'extra_hour_cents' => isset($data['extra_hour'])
                ? (int) round(((float) $data['extra_hour']) * 100)
                : null,
            'extra_person_cents' => isset($data['extra_person'])
                ? (int) round(((float) $data['extra_person']) * 100)
                : null,
            'deposit_required_cents' => isset($data['deposit_required'])
                ? (int) round(((float) $data['deposit_required']) * 100)
                : null,
            'conditions' => $data['conditions'] ?? null,
            'active' => $data['active'] ?? false,
        ];
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }

    private function ensureRangeBelongs(ListingPriceRange $range, Listing $listing, Team $team): void
    {
        abort_unless(
            $range->listing_id === $listing->id && $range->team_id === $team->id,
            404,
        );
    }
}
