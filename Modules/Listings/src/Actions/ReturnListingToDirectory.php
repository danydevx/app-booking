<?php

namespace Modules\Listings\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Listings\Events\ListingOwnershipTransferred;
use Modules\Listings\Models\Listing;
use Modules\Listings\Notifications\ListingReturnedToDirectory;
use Throwable;

final class ReturnListingToDirectory
{
    public function handle(Listing $listing, User $actor, string $reason): Listing
    {
        return DB::transaction(function () use ($listing, $actor, $reason): Listing {
            $directory = Team::query()->where('is_directory', true)->first();

            if ($directory === null) {
                throw ValidationException::withMessages([
                    'reason' => 'La cuenta Directorio no existe.',
                ]);
            }

            $listing = Listing::query()
                ->with('team')
                ->lockForUpdate()
                ->findOrFail($listing->id);

            if (! $listing->isClaimed() || $listing->claimed_by_user_id === null || $listing->team_id === $directory->id) {
                throw ValidationException::withMessages([
                    'reason' => 'La terraza no tiene un propietario que pueda devolverse al Directorio.',
                ]);
            }

            $previousTeam = $listing->team;
            $fromTeamId = (int) $listing->team_id;

            $listing->team_id = $directory->id;
            $listing->claimed_at = null;
            $listing->claimed_by_user_id = null;
            $listing->save();

            event(new ListingOwnershipTransferred($listing, $fromTeamId, (int) $directory->id));

            activity('moderation')
                ->performedOn($listing)
                ->causedBy($actor)
                ->withProperties([
                    'from_team_id' => $fromTeamId,
                    'to_team_id' => $directory->id,
                    'reason' => $reason,
                ])
                ->log('listing.claim.released');

            $owner = $previousTeam?->owner();

            if ($owner !== null) {
                try {
                    $owner->notify(new ListingReturnedToDirectory($listing, $reason));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            return $listing->refresh();
        });
    }
}
