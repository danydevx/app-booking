<?php

namespace Modules\Bookings\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->belongsToTeam($customer->team);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }
}
