<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Billing\Support\Entitlements;
use Modules\Listings\Actions\AddListingMedia;
use Modules\Listings\Actions\DeleteListingMedia;
use Modules\Listings\Actions\ReorderListingMedia;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Http\Requests\ReorderListingMediaRequest;
use Modules\Listings\Http\Requests\StoreListingMediaRequest;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;

class ListingMediaController extends Controller
{
    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function store(
        StoreListingMediaRequest $request,
        string $currentTeam,
        Listing $listing,
        AddListingMedia $addListingMedia,
    ): RedirectResponse {
        $team = $this->team($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $type = ListingMediaType::from((string) $request->validated('type'));
        $currentCount = $type === ListingMediaType::Image
            ? $listing->images()->count()
            : $listing->videos()->count();

        if (! $this->entitlements->canUpload($team, $type->value, $currentCount)) {
            $key = $type === ListingMediaType::Image
                ? 'photos_per_listing'
                : 'videos_per_listing';
            $limit = $this->entitlements->limit($team, $key);

            throw ValidationException::withMessages([
                'file' => $limit === 0
                    ? 'Tu plan actual no incluye este tipo de archivo.'
                    : "Tu plan actual permite máximo {$limit} archivos de este tipo por terraza.",
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
        $team = $this->team($request);
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
        $team = $this->team($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $reorderListingMedia->handle($listing, $request->validated('ids'));

        return back();
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
}
