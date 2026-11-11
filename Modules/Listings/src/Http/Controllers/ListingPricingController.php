<?php

namespace Modules\Listings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Http\Requests\SaveListingPricingRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPriceRange;
use Modules\Listings\Support\ListingCommercialPresenter;
use Modules\Listings\Support\ListingFormData;

class ListingPricingController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly ListingFormData $formData,
    ) {}

    public function edit(Request $request, string $currentTeam, Listing $listing): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        return Inertia::render('Listings/Pricing', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
            ],
            'pricing' => [
                'price_from' => $this->decimal($listing->price_from_cents),
                'daily_pricing' => ListingCommercialPresenter::dailyPricingForForm($listing),
                'included_hours' => $listing->included_hours,
                'included_people' => $listing->included_people,
                'extra_hour' => $this->decimal($listing->extra_hour_cents),
                'extra_person' => $this->decimal($listing->extra_person_cents),
                'deposit_required' => $this->decimal($listing->deposit_required_cents),
                'price_notes' => $listing->price_notes,
                'currency' => $listing->currency ?: 'MXN',
            ],
            'priceRanges' => $listing->priceRanges()
                ->get()
                ->map(fn (ListingPriceRange $range): array => ListingCommercialPresenter::ownerPriceRange($range, $team, $listing))
                ->all(),
            'urls' => [
                ...$this->formData->ownerUrls($team, $listing),
                'range_store' => route('listings.pricing.ranges.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
            ],
        ]);
    }

    public function update(
        SaveListingPricingRequest $request,
        string $currentTeam,
        Listing $listing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $data = $request->validated();

        $listing->forceFill([
            'price_from_cents' => $this->toCents($data['price_from'] ?? null),
            'daily_pricing' => $this->dailyPricing($data['daily_pricing'] ?? []),
            'included_hours' => $data['included_hours'] ?? null,
            'included_people' => $data['included_people'] ?? null,
            'extra_hour_cents' => $this->toCents($data['extra_hour'] ?? null),
            'extra_person_cents' => $this->toCents($data['extra_person'] ?? null),
            'deposit_required_cents' => $this->toCents($data['deposit_required'] ?? null),
            'price_notes' => $data['price_notes'] ?? null,
            'currency' => (string) config('listings.currency', 'MXN'),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Precios actualizados.']);

        return back();
    }

    private function decimal(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
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
}
