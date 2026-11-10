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
use Modules\Listings\Http\Requests\SaveListingPromotionRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPromotion;
use Modules\Listings\Support\ListingCommercialPresenter;
use Modules\Listings\Support\ListingFormData;

class ListingPromotionController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly ListingFormData $formData,
    ) {}

    public function index(Request $request, string $currentTeam, Listing $listing): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $promotions = $listing->promotions()
            ->get()
            ->map(fn (ListingPromotion $promotion): array => ListingCommercialPresenter::ownerPromotion($promotion, $team, $listing))
            ->all();

        return Inertia::render('Listings/Promotions/Index', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
            ],
            'promotions' => $promotions,
            'weekdays' => ListingPromotion::weekdayOptions(),
            'urls' => [
                ...$this->formData->ownerUrls($team, $listing),
                'store' => route('listings.promotions.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
            ],
        ]);
    }

    public function store(
        SaveListingPromotionRequest $request,
        string $currentTeam,
        Listing $listing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $promotion = new ListingPromotion($this->payload($request->validated()));
        $promotion->team_id = $team->id;
        $promotion->listing_id = $listing->id;
        $promotion->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción creada.']);

        return back();
    }

    public function update(
        SaveListingPromotionRequest $request,
        string $currentTeam,
        Listing $listing,
        ListingPromotion $promotion,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensurePromotionBelongs($promotion, $listing, $team);

        Gate::authorize('update', $listing);

        $promotion->update($this->payload($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción actualizada.']);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        ListingPromotion $promotion,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensurePromotionBelongs($promotion, $listing, $team);

        Gate::authorize('update', $listing);

        $promotion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Promoción eliminada.']);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'promotional_price_cents' => isset($data['promotional_price'])
                ? (int) round(((float) $data['promotional_price']) * 100)
                : null,
            'previous_price_cents' => isset($data['previous_price'])
                ? (int) round(((float) $data['previous_price']) * 100)
                : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'applies_on' => $data['applies_on'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'active' => $data['active'] ?? false,
        ];
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }

    private function ensurePromotionBelongs(ListingPromotion $promotion, Listing $listing, Team $team): void
    {
        abort_unless(
            $promotion->listing_id === $listing->id && $promotion->team_id === $team->id,
            404,
        );
    }
}
