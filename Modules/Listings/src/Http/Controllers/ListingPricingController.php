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
                'price_monday' => $this->decimal($listing->price_monday_cents),
                'price_tuesday' => $this->decimal($listing->price_tuesday_cents),
                'price_wednesday' => $this->decimal($listing->price_wednesday_cents),
                'price_thursday' => $this->decimal($listing->price_thursday_cents),
                'price_friday' => $this->decimal($listing->price_friday_cents),
                'price_saturday' => $this->decimal($listing->price_saturday_cents),
                'price_sunday' => $this->decimal($listing->price_sunday_cents),
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
            'price_monday_cents' => $this->toCents($data['price_monday'] ?? null),
            'price_tuesday_cents' => $this->toCents($data['price_tuesday'] ?? null),
            'price_wednesday_cents' => $this->toCents($data['price_wednesday'] ?? null),
            'price_thursday_cents' => $this->toCents($data['price_thursday'] ?? null),
            'price_friday_cents' => $this->toCents($data['price_friday'] ?? null),
            'price_saturday_cents' => $this->toCents($data['price_saturday'] ?? null),
            'price_sunday_cents' => $this->toCents($data['price_sunday'] ?? null),
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

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }
}
