<?php

namespace Modules\Listings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;

final class SuspendListing
{
    public function handle(Listing $listing, User $actor, string $reason): Listing
    {
        return DB::transaction(function () use ($listing, $actor, $reason): Listing {
            $listing->refresh();

            if ($listing->isSuspendedByPlatform()) {
                return $listing;
            }

            $previousStatus = $listing->status;

            $listing->forceFill([
                'status' => ListingStatus::Suspended,
                'suspended_at' => now(),
                'suspended_from_status' => $previousStatus,
                'suspension_reason' => trim($reason),
            ])->save();

            activity('moderation')
                ->performedOn($listing)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => trim($reason),
                    'previous_status' => $previousStatus->value,
                    'team_id' => $listing->team_id,
                ])
                ->log('listing.suspended');

            return $listing->refresh();
        });
    }
}
