<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;

final class RejectListingClaim
{
    public function handle(ListingClaim $claim, User $reviewer, string $reason): ListingClaim
    {
        return DB::transaction(function () use ($claim, $reviewer, $reason): ListingClaim {
            $claim = ListingClaim::query()
                ->with(['listing', 'claimant'])
                ->lockForUpdate()
                ->findOrFail($claim->id);

            if (! $claim->isPending()) {
                throw ValidationException::withMessages([
                    'claim' => 'Esta reclamación ya fue revisada.',
                ]);
            }

            $claim->forceFill([
                'status' => ListingClaimStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            activity('moderation')
                ->performedOn($claim->listing)
                ->causedBy($reviewer)
                ->withProperties([
                    'claim_id' => $claim->id,
                    'claimant_email' => $claim->claimant_email,
                    'reason' => $reason,
                ])
                ->log('listing.claim.rejected');

            return $claim->refresh()->load(['listing', 'claimant']);
        });
    }
}
