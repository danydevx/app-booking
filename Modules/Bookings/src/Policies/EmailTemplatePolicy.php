<?php

namespace Modules\Bookings\Policies;

use App\Models\Team;
use App\Models\User;
use Modules\Bookings\Models\EmailTemplate;

class EmailTemplatePolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->belongsToTeam($template->team);
    }

    public function delete(User $user, EmailTemplate $template): bool
    {
        return $user->belongsToTeam($template->team);
    }
}
