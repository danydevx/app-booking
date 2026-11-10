<?php

namespace Modules\Bookings\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\CustomerQuote;

class CustomerQuotePolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, CustomerQuote $quote): bool
    {
        return $user->belongsToTeam($quote->team);
    }

    public function update(User $user, CustomerQuote $quote): bool
    {
        return $this->view($user, $quote);
    }

    public function send(User $user, CustomerQuote $quote): bool
    {
        return $this->view($user, $quote);
    }

    public function convert(User $user, CustomerQuote $quote): bool
    {
        return $this->view($user, $quote);
    }
}
