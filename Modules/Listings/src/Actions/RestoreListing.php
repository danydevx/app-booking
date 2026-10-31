<?php

namespace Modules\Listings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

final class RestoreListing
{
    public function handle(Listing $listing, User $actor, string $reason): Listing
    {
        return DB::transaction(function () use ($listing, $actor, $reason): Listing {
            $listing->refresh();

            if (! $listing->isSuspendedByPlatform()) {
                return $listing;
            }

            $restoredStatus = $listing->suspended_from_status ?? ListingStatus::Draft;

            $listing->forceFill([
                'status' => $restoredStatus,
                'suspended_at' => null,
                'suspended_from_status' => null,
                'suspension_reason' => null,
            ])->save();

            activity('moderation')
                ->performedOn($listing)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => trim($reason),
                    'restored_status' => $restoredStatus->value,
                    'team_id' => $listing->team_id,
                ])
                ->log('listing.restored');

            return $listing->refresh();
        });
    }
}
