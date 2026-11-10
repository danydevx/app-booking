<?php

namespace Modules\Bookings\Policies;

use App\Enums\TeamRole;
use App\Models\User;
use Modules\Bookings\Models\CustomerTask;

class CustomerTaskPolicy
{
    public function update(User $user, CustomerTask $task): bool
    {
        return $this->canManage($user, $task);
    }

    public function complete(User $user, CustomerTask $task): bool
    {
        return $this->canManage($user, $task);
    }

    public function cancel(User $user, CustomerTask $task): bool
    {
        return $this->canManage($user, $task);
    }

    private function canManage(User $user, CustomerTask $task): bool
    {
        if (! $user->belongsToTeam($task->team)) {
            return false;
        }

        if ($task->assigned_to === $user->id || $task->created_by === $user->id) {
            return true;
        }

        return $user->teamRole($task->team)?->isAtLeast(TeamRole::Admin) ?? false;
    }
}
