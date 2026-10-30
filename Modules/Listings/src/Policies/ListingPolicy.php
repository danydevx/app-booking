<?php

namespace Modules\Listings\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Modules\Listings\Models\Listing;

class ListingPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Listing $listing): bool
    {
        return $this->belongsToListingTeam($user, $listing);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $this->belongsToListingTeam($user, $listing);
    }

    public function publish(User $user, Listing $listing): bool
    {
        return $this->canManageListing($user, $listing);
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->canManageListing($user, $listing);
    }

    private function belongsToListingTeam(User $user, Listing $listing): bool
    {
        $team = $listing->team;

        return $team !== null && $user->belongsToTeam($team);
    }

    private function canManageListing(User $user, Listing $listing): bool
    {
        $team = $listing->team;

        if ($team === null) {
            return false;
        }

        $role = $user->teamRole($team);

        return $role?->isAtLeast(TeamRole::Admin) ?? false;
    }
}
