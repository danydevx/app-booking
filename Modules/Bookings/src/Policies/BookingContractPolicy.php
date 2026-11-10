<?php

namespace Modules\Bookings\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\BookingContract;

class BookingContractPolicy
{
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, BookingContract $contract): bool
    {
        return $user->belongsToTeam($contract->team);
    }

    public function publish(User $user, BookingContract $contract): bool
    {
        return $user->belongsToTeam($contract->team);
    }
}
