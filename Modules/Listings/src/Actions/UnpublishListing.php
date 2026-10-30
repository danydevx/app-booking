<?php

namespace Modules\Listings\Actions;

use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

final class UnpublishListing
{
    public function handle(Listing $listing): Listing
    {
        $listing->forceFill([
            'status' => ListingStatus::Draft,
            'published_at' => null,
        ])->save();

        return $listing->refresh();
    }
}
