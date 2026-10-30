<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

final class CreateListing
{
    public function __construct(
        private readonly GenerateListingSlug $generateSlug,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Listing
    {
        return DB::transaction(function () use ($team, $data): Listing {
            $listing = new Listing($data);

            $listing->team_id = $team->id;
            $listing->type = (string) config('listings.default_type', 'terrace');
            $listing->slug = $this->generateSlug->handle((string) $data['name']);
            $listing->timezone = $team->timezone ?: (string) config('listings.default_timezone', 'America/Mexico_City');
            $listing->status = ListingStatus::Draft;
            $listing->published_at = null;
            $listing->save();

            return $listing;
        });
    }
}
