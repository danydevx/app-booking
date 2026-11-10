<?php

namespace Modules\Bookings\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\CustomerTag;

class CustomerTagPolicy
{
    public function create(User $user, Team $team): bool
    {
        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    public function delete(User $user, CustomerTag $tag): bool
    {
        return $user->belongsToTeam($tag->team)
            && ($user->teamRole($tag->team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
