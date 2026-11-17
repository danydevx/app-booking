<?php

namespace Modules\Platform\Actions;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ListingClaimRole;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;
use RuntimeException;

final class SubmitListingClaim
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        Listing $listing,
        User $user,
        array $data,
        ?UploadedFile $evidence = null,
    ): ListingClaim {
        return DB::transaction(function () use ($listing, $user, $data, $evidence): ListingClaim {
            $listing = Listing::query()
                ->with('team')
                ->lockForUpdate()
                ->findOrFail($listing->id);

            if (! $listing->isClaimable()) {
                throw new RuntimeException('Esta terraza ya fue reclamada o no está disponible para reclamo.');
            }

            $pending = ListingClaim::query()
                ->where('listing_id', $listing->id)
                ->where('user_id', $user->id)
                ->pending()
                ->exists();

            if ($pending) {
                throw new RuntimeException('Ya tienes una reclamación pendiente para esta terraza.');
            }

            $targetTeamId = $data['target_team_id'] ?? null;

            if ($targetTeamId !== null) {
                $targetTeam = Team::query()->find((int) $targetTeamId);

                if (
                    $targetTeam === null
                    || $targetTeam->trashed()
                    || $targetTeam->isDirectory()
                    || ! $user->belongsToTeam($targetTeam)
                    || ! ($user->teamRole($targetTeam)?->isAtLeast(TeamRole::Admin) ?? false)
                ) {
                    throw new RuntimeException('No tienes permisos para administrar la cuenta seleccionada.');
                }
            }

            $evidencePath = null;
            $evidenceName = null;
            $evidenceMime = null;
            $evidenceSize = null;

            if ($evidence !== null) {
                $disk = (string) config('listings.claims.evidence_disk', 'local');
                $stored = $evidence->store("listing-claims/{$listing->id}", $disk);

                if (! is_string($stored)) {
                    throw new RuntimeException('No se pudo guardar el documento de apoyo.');
                }

                $evidencePath = $stored;
                $evidenceName = $evidence->getClientOriginalName();
                $evidenceMime = $evidence->getMimeType();
                $evidenceSize = (int) $evidence->getSize();
            }

            $claim = ListingClaim::query()->create([
                'listing_id' => $listing->id,
                'team_id' => $listing->team_id,
                'user_id' => $user->id,
                'target_team_id' => $data['target_team_id'] ?? null,
                'business_name' => (string) $data['business_name'],
                'role' => ListingClaimRole::from((string) $data['role']),
                'claimant_name' => (string) $data['claimant_name'],
                'claimant_email' => $user->email,
                'claimant_phone' => (string) $data['claimant_phone'],
                'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
                'message' => (string) $data['message'],
                'evidence_path' => $evidencePath,
                'evidence_name' => $evidenceName,
                'evidence_mime' => $evidenceMime,
                'evidence_size' => $evidenceSize,
                'status' => ListingClaimStatus::Pending,
            ]);

            activity('moderation')
                ->performedOn($listing)
                ->causedBy($user)
                ->withProperties([
                    'claim_id' => $claim->id,
                    'claimant_email' => $user->email,
                    'business_name' => $claim->business_name,
                ])
                ->log('listing.claim.submitted');

            return $claim->load(['listing', 'claimant', 'targetTeam']);
        });
    }
}
