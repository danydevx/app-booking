<?php

namespace Modules\Listings\Actions;

use Illuminate\Http\UploadedFile;
use Modules\Listings\Models\Listing;

final class UpdateListing
{
    public function __construct(
        private readonly StoreListingLogo $storeLogo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Listing $listing, array $data): Listing
    {
        $hasEventTypes = array_key_exists('event_types', $data);
        $eventTypeIds = array_map('intval', $data['event_types'] ?? []);
        $logo = $data['logo'] ?? null;
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        unset($data['event_types'], $data['logo'], $data['remove_logo']);

        $listing->update($data);

        if ($hasEventTypes) {
            // Inactive event types are not offered in the form. Preserve any
            // existing relationship instead of silently deleting it when the
            // owner saves another field.
            $inactiveIds = $listing->eventTypes()
                ->where('is_active', false)
                ->pluck('listing_event_types.id')
                ->all();

            $listing->eventTypes()->sync(array_values(array_unique([
                ...$eventTypeIds,
                ...$inactiveIds,
            ])));
        }

        if ($logo !== null || $removeLogo) {
            $this->storeLogo->handle(
                $listing,
                $logo instanceof UploadedFile ? $logo : null,
                $removeLogo,
            );
        }

        return $listing->refresh();
    }
}
