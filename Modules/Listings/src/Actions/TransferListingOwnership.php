<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Listings\Events\ListingOwnershipTransferred;
use Modules\Listings\Models\Listing;
use RuntimeException;

final class TransferListingOwnership
{
    /**
     * @param  array{name?: string|null, email?: string|null, phone?: string|null, whatsapp?: string|null}  $contact
     */
    public function handle(
        Listing $listing,
        Team $targetTeam,
        User $actor,
        ?User $claimedBy = null,
        array $contact = [],
    ): Listing {
        if ($targetTeam->isDirectory()) {
            throw new RuntimeException('No puedes transferir una terraza a la cuenta Directorio.');
        }

        if ($targetTeam->trashed()) {
            throw new RuntimeException('La cuenta de destino no está disponible.');
        }

        return DB::transaction(function () use ($listing, $targetTeam, $actor, $claimedBy, $contact): Listing {
            $listing = Listing::query()
                ->with('team')
                ->lockForUpdate()
                ->findOrFail($listing->id);

            if (! $listing->isClaimable()) {
                throw new RuntimeException('La terraza ya fue reclamada o no pertenece al Directorio.');
            }

            $fromTeamId = (int) $listing->team_id;

            $listing->team_id = $targetTeam->id;
            $listing->claimed_at = Carbon::now();
            $listing->claimed_by_user_id = $claimedBy instanceof User
                ? $claimedBy->id
                : $actor->id;

            if (filled($contact['name'] ?? null)) {
                $listing->contact_name = (string) $contact['name'];
            }

            if (filled($contact['email'] ?? null)) {
                $listing->contact_email = (string) $contact['email'];
            }

            if (filled($contact['phone'] ?? null)) {
                $listing->contact_phone = (string) $contact['phone'];
            }

            // The claimed contact information is the verified source. Clear any
            // imported WhatsApp number until the owner confirms it.
            $listing->whatsapp_phone = filled($contact['whatsapp'] ?? null)
                ? (string) $contact['whatsapp']
                : null;

            $listing->save();

            event(new ListingOwnershipTransferred($listing, $fromTeamId, (int) $targetTeam->id));

            return $listing->refresh();
        });
    }
}
