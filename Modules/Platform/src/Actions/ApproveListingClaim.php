<?php

namespace Modules\Platform\Actions;

use App\Actions\Teams\CreateTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Listings\Actions\TransferListingOwnership;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;

final class ApproveListingClaim
{
    public function __construct(
        private readonly CreateTeam $createTeam,
        private readonly TransferListingOwnership $transfer,
    ) {}

    /**
     * @return array{claim: ListingClaim, auto_rejected: array<int, ListingClaim>}
     */
    public function handle(ListingClaim $claim, User $reviewer): array
    {
        return DB::transaction(function () use ($claim, $reviewer): array {
            $claim = ListingClaim::query()
                ->with(['claimant', 'listing.team'])
                ->lockForUpdate()
                ->findOrFail($claim->id);

            if (! $claim->isPending()) {
                throw ValidationException::withMessages([
                    'claim' => 'Esta reclamación ya fue revisada.',
                ]);
            }

            $claimant = $claim->claimant;

            if ($claimant === null) {
                throw ValidationException::withMessages([
                    'claim' => 'La cuenta del solicitante ya no existe.',
                ]);
            }

            $listing = Listing::query()
                ->with('team')
                ->lockForUpdate()
                ->findOrFail($claim->listing_id);

            if (! $listing->isClaimable()) {
                throw ValidationException::withMessages([
                    'claim' => 'La terraza ya fue reclamada o no pertenece al Directorio.',
                ]);
            }

            $targetTeam = $this->resolveTargetTeam($claim, $claimant);

            $this->transfer->handle(
                $listing,
                $targetTeam,
                $reviewer,
                $claimant,
                [
                    'name' => $claim->claimant_name,
                    'email' => $claim->claimant_email,
                    'phone' => $claim->claimant_phone,
                    'whatsapp' => $claim->whatsapp_phone,
                ],
            );

            $claim->forceFill([
                'target_team_id' => $targetTeam->id,
                'status' => ListingClaimStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $autoRejected = ListingClaim::query()
                ->where('listing_id', $listing->id)
                ->whereKeyNot($claim->id)
                ->pending()
                ->get();

            if ($autoRejected->isNotEmpty()) {
                $autoRejected->each(function (ListingClaim $other) use ($reviewer): void {
                    $other->forceFill([
                        'status' => ListingClaimStatus::Rejected,
                        'rejection_reason' => 'La terraza ya fue reclamada por otro propietario.',
                        'reviewed_by_id' => $reviewer->id,
                        'reviewed_at' => now(),
                    ])->save();
                });
            }

            activity('moderation')
                ->performedOn($listing)
                ->causedBy($reviewer)
                ->withProperties([
                    'claim_id' => $claim->id,
                    'claimant_email' => $claim->claimant_email,
                    'target_team_id' => $targetTeam->id,
                    'target_team' => $targetTeam->name,
                ])
                ->log('listing.claim.approved');

            return [
                'claim' => $claim->refresh()->load(['listing', 'claimant', 'targetTeam']),
                'auto_rejected' => $autoRejected->values()->all(),
            ];
        });
    }

    private function resolveTargetTeam(ListingClaim $claim, User $claimant): Team
    {
        if ($claim->target_team_id !== null) {
            $team = Team::query()->find($claim->target_team_id);

            if (
                $team === null
                || $team->trashed()
                || $team->isDirectory()
                || ! $claimant->belongsToTeam($team)
            ) {
                throw ValidationException::withMessages([
                    'claim' => 'El solicitante ya no tiene acceso a la cuenta seleccionada.',
                ]);
            }

            return $team;
        }

        return $this->createTeam->handle($claimant, $claim->business_name);
    }
}
