<?php

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Http\Request;

trait ResolvesCurrentTeam
{
    private function currentTeam(Request $request): Team
    {
        return Team::query()
            ->whereKey($request->user()?->current_team_id)
            ->firstOrFail();
    }
}
