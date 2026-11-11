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
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Http\Requests\SaveListingServiceRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingService;
use Modules\Listings\Support\ListingCommercialPresenter;
use Modules\Listings\Support\ListingFormData;
use Modules\Listings\Support\TerraceServiceCatalog;

class ListingServiceController extends Controller
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

        $services = $listing->commercialServices()
            ->get()
            ->map(fn (ListingService $service): array => ListingCommercialPresenter::ownerService($service, $team, $listing))
            ->all();

        return Inertia::render('Listings/Services/Index', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
            ],
            'services' => $services,
            'serviceGroups' => TerraceServiceCatalog::groups(),
            'priceUnits' => ListingServicePriceUnit::options(),
            'urls' => [
                ...$this->formData->ownerUrls($team, $listing),
                'store' => route('listings.services.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
            ],
        ]);
    }

    public function store(
        SaveListingServiceRequest $request,
        string $currentTeam,
        Listing $listing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $data = $request->validated();
        $service = new ListingService($this->payload($data));
        $service->team_id = $team->id;
        $service->listing_id = $listing->id;
        $service->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio creado.']);

        return back();
    }

    public function update(
        SaveListingServiceRequest $request,
        string $currentTeam,
        Listing $listing,
        ListingService $service,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensureServiceBelongs($service, $listing, $team);

        Gate::authorize('update', $listing);

        $service->update($this->payload($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio actualizado.']);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        ListingService $service,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensureServiceBelongs($service, $listing, $team);

        Gate::authorize('update', $listing);

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio eliminado.']);

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'service_key' => $data['service_key'],
            'description' => $data['description'] ?? null,
            'is_included' => $data['is_included'] ?? false,
            'price_cents' => isset($data['price'])
                ? (int) round(((float) $data['price']) * 100)
                : null,
            'price_unit' => $data['price_unit'],
            'active' => $data['active'] ?? false,
        ];
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }

    private function ensureServiceBelongs(ListingService $service, Listing $listing, Team $team): void
    {
        abort_unless(
            $service->listing_id === $listing->id && $service->team_id === $team->id,
            404,
        );
    }
}
