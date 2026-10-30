<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Listings\Actions\CreateListing;
use Modules\Listings\Actions\DeleteListing;
use Modules\Listings\Actions\PublishListing;
use Modules\Listings\Actions\UnpublishListing;
use Modules\Listings\Actions\UpdateListing;
use Modules\Listings\Http\Requests\SaveListingRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\ListingPresenter;
use Modules\Listings\Support\TerraceServiceCatalog;

class ListingController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $this->team($request);

        Gate::authorize('viewAny', [Listing::class, $team]);

        $listings = Listing::query()
            ->where('team_id', $team->id)
            ->with('coverImage')
            ->withCount([
                'media as images_count' => fn ($query) => $query->where('type', 'image'),
                'media as videos_count' => fn ($query) => $query->where('type', 'video'),
            ])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $listings->through(
            fn (Listing $listing): array => ListingPresenter::ownerCard($listing, $team->slug),
        );

        return Inertia::render('Listings/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'listings' => $listings,
            'limits' => $this->mediaLimits(),
            'canCreate' => Gate::allows('create', [Listing::class, $team]),
            'urls' => [
                'index' => route('listings.index', ['current_team' => $team->slug]),
                'create' => route('listings.create', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $team = $this->team($request);

        Gate::authorize('create', [Listing::class, $team]);

        return Inertia::render('Listings/Create', [
            'services' => $this->services(),
            'limits' => $this->mediaLimits(),
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
        $team = $this->team($request);

        Gate::authorize('create', [Listing::class, $team]);

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
        $team = $this->team($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $listing->load('media');

        return Inertia::render('Listings/Edit', [
            'listing' => ListingPresenter::ownerForm($listing, $team->slug),
            'services' => $this->services(),
            'limits' => $this->mediaLimits(),
            'canPublish' => Gate::allows('publish', $listing),
            'canDelete' => Gate::allows('delete', $listing),
            'urls' => $this->ownerUrls($team, $listing),
        ]);
    }

    public function update(
        SaveListingRequest $request,
        string $currentTeam,
        Listing $listing,
        UpdateListing $updateListing,
    ): RedirectResponse {
        $team = $this->team($request);
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
        $team = $this->team($request);
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
        $team = $this->team($request);
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
        $team = $this->team($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('delete', $listing);

        $deleteListing->handle($listing);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terraza eliminada.',
        ]);

        return to_route('listings.index', ['current_team' => $team->slug]);
    }

    private function team(Request $request): Team
    {
        return Team::query()
            ->whereKey($request->user()?->current_team_id)
            ->firstOrFail();
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function services(): array
    {
        return TerraceServiceCatalog::options();
    }

    /**
     * @return array<string, int>
     */
    private function mediaLimits(): array
    {
        return [
            'max_images' => (int) config('listings.media.max_images', 20),
            'max_videos' => (int) config('listings.media.max_videos', 3),
            'max_image_kb' => (int) config('listings.media.max_image_kb', 5120),
            'max_video_kb' => (int) config('listings.media.max_video_kb', 51200),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function ownerUrls(Team $team, Listing $listing): array
    {
        return [
            'index' => route('listings.index', ['current_team' => $team->slug]),
            'update' => route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'publish' => route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'unpublish' => route('listings.unpublish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'destroy' => route('listings.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'media_store' => route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'media_reorder' => route('listings.media.reorder', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
        ];
    }
}
