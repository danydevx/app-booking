<?php

namespace Modules\Listings\Queries;

use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\ListingPresenter;

final class OwnerListingQuery
{
    public function countForTeam(Team $team): int
    {
        return Listing::query()->where('team_id', $team->id)->count();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(Team $team): LengthAwarePaginator
    {
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

        return $listings->through(
            fn (Listing $listing): array => ListingPresenter::ownerCard($listing, $team->slug),
        );
    }
}
