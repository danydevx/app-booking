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
        $dailyPricing = $this->dailyPricing($data['daily_pricing'] ?? []);
        $dailyPrices = [];

        foreach ($dailyPricing ?? [] as $row) {
            if ($row['price_cents'] !== null) {
                $dailyPrices[] = $row['price_cents'];
            }
        }

        $legacyPrice = $this->toCents($data['price'] ?? null);

        return [
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'price_cents' => $dailyPrices !== [] ? min($dailyPrices) : ($legacyPrice ?? 0),
            'daily_pricing' => $dailyPricing,
            'included_hours' => $data['included_hours'] ?? null,
            'included_people' => $data['included_people'] ?? null,
            'extra_hour_cents' => $this->toCents($data['extra_hour'] ?? null),
            'extra_person_cents' => $this->toCents($data['extra_person'] ?? null),
            'deposit_required_cents' => $this->toCents($data['deposit_required'] ?? null),
            'conditions' => $data['conditions'] ?? null,
            'active' => $data['active'] ?? false,
        ];
    }

    private function toCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array{hours: int|null, entry_time: string|null, exit_time: string|null, price_cents: int|null}>|null
     */
    private function dailyPricing(array $input): ?array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $pricing = [];

        foreach ($days as $day) {
            $row = $input[$day] ?? null;

            if (! is_array($row)) {
                continue;
            }

            $hours = isset($row['hours']) && $row['hours'] !== ''
                ? (int) $row['hours']
                : null;
            $entryTime = $this->time($row['entry_time'] ?? null);
            $exitTime = $this->time($row['exit_time'] ?? null);
            $priceCents = $this->toCents($row['price'] ?? null);

            if ($hours === null && $entryTime === null && $exitTime === null && $priceCents === null) {
                continue;
            }

            $pricing[$day] = [
                'hours' => $hours,
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'price_cents' => $priceCents,
            ];
        }

        return $pricing === [] ? null : $pricing;
    }

    private function time(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
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
