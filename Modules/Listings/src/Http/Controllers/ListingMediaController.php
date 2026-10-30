<?php

namespace Modules\Listings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
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
    public function store(
        StoreListingMediaRequest $request,
        string $currentTeam,
        Listing $listing,
        AddListingMedia $addListingMedia,
    ): RedirectResponse {
        $team = $this->team($request);
        $this->ensureOwned($listing, $team);

        Gate::authorize('update', $listing);

        $addListingMedia->handle(
            $listing,
            $request->file('file'),
            ListingMediaType::from((string) $request->validated('type')),
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
