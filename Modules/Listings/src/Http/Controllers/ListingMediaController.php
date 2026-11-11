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
use Modules\Listings\Actions\AddListingMedia;
use Modules\Listings\Actions\DeleteListingMedia;
use Modules\Listings\Actions\ReorderListingMedia;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Http\Requests\ReorderListingMediaRequest;
use Modules\Listings\Http\Requests\StoreListingMediaRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Modules\Listings\Support\ListingFormData;
use Modules\Listings\Support\ListingPresenter;

class ListingMediaController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly ListingFormData $formData,
    ) {}

    public function index(
        Request $request,
        string $currentTeam,
        Listing $listing,
    ): Response {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $listing->load('media');

        return Inertia::render('Listings/Photos', [
            'listing' => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'status' => $listing->status->value,
                'status_label' => $listing->status->label(),
                'public_url' => $listing->isPublished()
                    ? route('public.listings.show', ['listing' => $listing->slug])
                    : null,
            ],
            'media' => $listing->media
                ->map(fn (ListingMedia $media): array => ListingPresenter::ownerMedia($media, $listing, $team->slug))
                ->all(),
            'limits' => $this->formData->mediaLimits($team),
            'urls' => $this->formData->ownerUrls($team, $listing),
        ]);
    }

    public function store(
        StoreListingMediaRequest $request,
        string $currentTeam,
        Listing $listing,
        AddListingMedia $addListingMedia,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $type = ListingMediaType::Image;
        $currentCount = $listing->images()->count();

        if (! $this->entitlements->canUpload($team, $type->value, $currentCount)) {
            $limit = $this->entitlements->limit($team, 'photos_per_listing');

            throw ValidationException::withMessages([
                'file' => $limit === 0
                    ? 'Tu plan actual no incluye fotos.'
                    : "Tu plan actual permite máximo {$limit} fotos por terraza.",
            ]);
        }

        $addListingMedia->handle(
            $listing,
            $request->file('file'),
            $type,
            $request->validated('alt_text'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Archivo agregado.',
        ]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Listing $listing,
        ListingMedia $media,
        DeleteListingMedia $deleteListingMedia,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        abort_unless($media->listing_id === $listing->id, 404);

        Gate::authorize('update', $listing);

        $deleteListingMedia->handle($media);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Archivo eliminado.',
        ]);

        return back();
    }

    public function reorder(
        ReorderListingMediaRequest $request,
        string $currentTeam,
        Listing $listing,
        ReorderListingMedia $reorderListingMedia,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $reorderListingMedia->handle($listing, $request->validated('ids'));

        return back();
    }

    private function ensureOwned(Listing $listing, Team $team): void
    {
        abort_unless($listing->team_id === $team->id, 404);
    }
}
