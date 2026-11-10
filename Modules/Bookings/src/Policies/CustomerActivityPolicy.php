<?php

namespace Modules\Bookings\Policies;

use App\Enums\TeamRole;
use App\Models\User;
use Modules\Bookings\Models\CustomerActivity;

class CustomerActivityPolicy
{
    public function delete(User $user, CustomerActivity $activity): bool
    {
        if (! $user->belongsToTeam($activity->team)) {
            return false;
        }

        return $activity->user_id === $user->id
            || ($user->teamRole($activity->team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
