<?php

namespace Modules\Listings\Actions;

use Modules\Listings\Models\Listing;

final class UpdateListing
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Listing $listing, array $data): Listing
    {
        $listing->update($data);

        return $listing->refresh();
    }
}
