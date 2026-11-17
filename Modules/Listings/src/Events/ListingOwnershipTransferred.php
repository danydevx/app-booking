<?php

namespace Modules\Listings\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Listings\Models\Listing;

class ListingOwnershipTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public int $fromTeamId,
        public int $toTeamId,
    ) {}
}
