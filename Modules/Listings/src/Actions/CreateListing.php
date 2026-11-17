<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

final class CreateListing
{
    public function __construct(
        private readonly GenerateListingSlug $generateSlug,
        private readonly StoreListingLogo $storeLogo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): Listing
    {
        $eventTypeIds = array_map('intval', $data['event_types'] ?? []);
        $logo = $data['logo'] ?? null;
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        unset($data['event_types'], $data['logo'], $data['remove_logo']);

        return DB::transaction(function () use ($team, $data, $eventTypeIds, $logo, $removeLogo): Listing {
            $listing = new Listing($data);

            $listing->team_id = $team->id;
            $listing->type = (string) config('listings.default_type', 'terrace');
            $listing->slug = $this->generateSlug->handle((string) $data['name']);
            $listing->timezone = $team->timezone ?: (string) config('listings.default_timezone', 'America/Mexico_City');
            $listing->status = ListingStatus::Draft;
            $listing->published_at = null;
            $listing->claimed_at = Carbon::now();
            $listing->save();

            if ($eventTypeIds !== []) {
                $listing->eventTypes()->sync($eventTypeIds);
            }

            if ($logo !== null || $removeLogo) {
                $this->storeLogo->handle(
                    $listing,
                    $logo instanceof UploadedFile ? $logo : null,
                    $removeLogo,
                );
            }

            return $listing;
        });
    }
}
