<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Models\Listing;

final class ReorderListingMedia
{
    /**
     * @param  array<int, int>  $mediaIds
     */
    public function handle(Listing $listing, array $mediaIds): void
    {
        /** @var Collection<int, int> $ownedIds */
        $ownedIds = $listing->media()->pluck('id');

        abort_unless(
            collect($mediaIds)->sort()->values()->all() === $ownedIds->sort()->values()->all(),
            422,
        );

        DB::transaction(function () use ($listing, $mediaIds): void {
            foreach ($mediaIds as $position => $mediaId) {
                $listing->media()
                    ->whereKey($mediaId)
                    ->update(['sort_order' => $position]);
            }
        });
    }
}
