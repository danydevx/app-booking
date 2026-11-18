<?php

namespace Modules\Listings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Listings\Http\Requests\SaveListingPackageRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Support\CommercialServiceCatalog;
use Modules\Listings\Support\ListingCommercialPresenter;
use Modules\Listings\Support\ListingFormData;
use Modules\Listings\Support\TerraceServiceCatalog;

class ListingPackageController extends Controller
{
    use ResolvesCurrentTeam;

    private const LIMIT_KEY = 'packages_per_listing';

    public function __construct(
        private readonly ListingFormData $formData,
        private readonly Entitlements $entitlements,
    ) {}

    public function index(Request $request, string $currentTeam, Listing $listing): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $packages = $listing->packages()->get();
        $limit = $this->entitlements->limit($team, self::LIMIT_KEY);

        return Inertia::render('Listings/Packages/Index', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
            ],
            'packages' => $packages
                ->map(fn (ListingPackage $package): array => ListingCommercialPresenter::ownerPackage($package, $team, $listing))
                ->all(),
            'limits' => [
                'max' => $limit,
                'used' => $packages->count(),
                'can_create' => $this->entitlements->canAddItem($team, self::LIMIT_KEY, $packages->count()),
            ],
            'billing_url' => route('billing.index', ['current_team' => $team->slug]),
            'serviceGroups' => CommercialServiceCatalog::groups(),
            'characteristics' => TerraceServiceCatalog::groupsFor($listing->services ?? []),
            'urls' => [
                ...$this->formData->ownerUrls($team, $listing),
                'store' => route('listings.packages.store', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]),
            ],
        ]);
    }

    public function store(
        SaveListingPackageRequest $request,
        string $currentTeam,
        Listing $listing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);
        $this->ensureWithinPlanLimit($team, $listing);

        $data = $request->validated();
        $package = new ListingPackage($this->payload($data));
        $package->team_id = $team->id;
        $package->listing_id = $listing->id;
        $package->sort_order = $data['sort_order']
            ?? ((int) $listing->packages()->max('sort_order')) + 1;
        $package->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paquete creado.']);

        return back();
    }

    public function update(
        SaveListingPackageRequest $request,
        string $currentTeam,
        Listing $listing,
        ListingPackage $package,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensurePackageBelongs($package, $listing, $team);

        Gate::authorize('update', $listing);

        $data = $request->validated();
        $package->update([
            ...$this->payload($data),
            'sort_order' => $data['sort_order'] ?? $package->sort_order,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paquete actualizado.']);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        ListingPackage $package,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);
        $this->ensurePackageBelongs($package, $listing, $team);

        Gate::authorize('update', $listing);

        $package->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Paquete eliminado.']);

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
            'description' => $data['description'] ?? null,
            'price_cents' => (int) round(((float) $data['price']) * 100),
            'duration_hours' => $data['duration_hours'],
            'included_people' => $data['included_people'],
            'included_services' => $data['included_services'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'active' => $data['active'] ?? false,
        ];
    }

    private function ensureWithinPlanLimit(Team $team, Listing $listing): void
    {
        $count = $listing->packages()->count();

        if ($this->entitlements->canAddItem($team, self::LIMIT_KEY, $count)) {
            return;
        }

        $limit = $this->entitlements->limit($team, self::LIMIT_KEY);

        throw ValidationException::withMessages([
            'name' => $limit === 0
                ? 'Tu plan actual no incluye paquetes por terraza.'
                : "Tu plan actual permite máximo {$limit} paquetes por terraza. Actualiza el plan para agregar más.",
        ]);
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }

    private function ensurePackageBelongs(ListingPackage $package, Listing $listing, Team $team): void
    {
        abort_unless(
            $package->listing_id === $listing->id && $package->team_id === $team->id,
            404,
        );
    }
}
