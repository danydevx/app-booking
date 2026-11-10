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
                'price_weekday' => $this->decimal($listing->price_weekday_cents),
                'price_weekend' => $this->decimal($listing->price_weekend_cents),
                'included_hours' => $listing->included_hours,
                'included_people' => $listing->included_people,
                'extra_hour' => $this->decimal($listing->extra_hour_cents),
                'extra_person' => $this->decimal($listing->extra_person_cents),
                'deposit_required' => $this->decimal($listing->deposit_required_cents),
                'price_notes' => $listing->price_notes,
                'currency' => $listing->currency ?: 'MXN',
            ],
            'urls' => $this->formData->ownerUrls($team, $listing),
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
            'price_weekday_cents' => $this->toCents($data['price_weekday'] ?? null),
            'price_weekend_cents' => $this->toCents($data['price_weekend'] ?? null),
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
