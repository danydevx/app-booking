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
use Modules\Listings\Actions\CreateListing;
use Modules\Listings\Actions\DeleteListing;
use Modules\Listings\Actions\PublishListing;
use Modules\Listings\Actions\UnpublishListing;
use Modules\Listings\Actions\UpdateListing;
use Modules\Listings\Http\Requests\SaveListingRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Queries\OwnerListingQuery;
use Modules\Listings\Support\ListingFormData;
use Modules\Listings\Support\ListingPresenter;

class ListingController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly OwnerListingQuery $listings,
        private readonly ListingFormData $formData,
    ) {}

    public function index(Request $request): Response
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Listing::class, $team]);

        $listingCount = $this->listings->countForTeam($team);
        $plan = $this->entitlements->planFor($team);
        $listings = $this->listings->paginate($team);

        return Inertia::render('Listings/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'listings' => $listings,
            'limits' => $this->formData->mediaLimits($team),
            'canCreate' => Gate::allows('create', [Listing::class, $team])
                && $this->entitlements->canCreateListing($team, $listingCount),
            'plan' => [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'listing_limit' => $plan->limit('listings'),
            ],
            'urls' => [
                'index' => route('listings.index', ['current_team' => $team->slug]),
                'create' => route('listings.create', ['current_team' => $team->slug]),
                'billing' => route('billing.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Listing::class, $team]);

        $listingCount = $this->listings->countForTeam($team);

        if (! $this->entitlements->canCreateListing($team, $listingCount)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Tu plan actual ya alcanzó el límite de terrazas.',
            ]);

            return to_route('billing.index', ['current_team' => $team->slug]);
        }

        return Inertia::render('Listings/Create', [
            'services' => $this->formData->services(),
            'limits' => $this->formData->mediaLimits($team),
            'urls' => [
                'index' => route('listings.index', ['current_team' => $team->slug]),
                'store' => route('listings.store', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        SaveListingRequest $request,
        CreateListing $createListing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Listing::class, $team]);

        $listingCount = $this->listings->countForTeam($team);

        if (! $this->entitlements->canCreateListing($team, $listingCount)) {
            throw ValidationException::withMessages([
                'name' => 'Tu plan actual ya alcanzó el límite de terrazas. Actualiza el plan para crear otra.',
            ]);
        }

        $listing = $createListing->handle($team, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza creada. Agrega fotos y completa la ubicación para publicarla.',
        ]);

        return to_route('listings.edit', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);
    }

    public function edit(Request $request, string $currentTeam, Listing $listing): Response
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $listing->load('media');

        return Inertia::render('Listings/Edit', [
            'listing' => ListingPresenter::ownerForm($listing, $team->slug),
            'services' => $this->formData->services(),
            'limits' => $this->formData->mediaLimits($team),
            'canPublish' => Gate::allows('publish', $listing),
            'canDelete' => Gate::allows('delete', $listing),
            'urls' => $this->formData->ownerUrls($team, $listing),
        ]);
    }

    public function update(
        SaveListingRequest $request,
        string $currentTeam,
        Listing $listing,
        UpdateListing $updateListing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $updateListing->handle($listing, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza actualizada.',
        ]);

        return back();
    }

    public function publish(
        Request $request,
        string $currentTeam,
        Listing $listing,
        PublishListing $publishListing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('publish', $listing);

        $publishListing->handle($listing);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza publicada.',
        ]);

        return back();
    }

    public function unpublish(
        Request $request,
        string $currentTeam,
        Listing $listing,
        UnpublishListing $unpublishListing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('publish', $listing);

        $unpublishListing->handle($listing);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza despublicada.',
        ]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        DeleteListing $deleteListing,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('delete', $listing);

        $deleteListing->handle($listing);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza eliminada.',
        ]);

        return to_route('listings.index', ['current_team' => $team->slug]);
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }
}
