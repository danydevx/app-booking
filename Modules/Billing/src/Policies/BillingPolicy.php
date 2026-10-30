<?php

namespace Modules\Billing\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class BillingPolicy
{
    public function manage(User $user, Team $team): bool
    {
        return $user->teamRole($team) === TeamRole::Owner;
    }
}
