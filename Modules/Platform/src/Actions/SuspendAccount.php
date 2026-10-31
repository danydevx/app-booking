<?php

namespace Modules\Platform\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Notifications\AccountSuspended;

final class SuspendAccount
{
    public function handle(Team $team, User $actor, string $reason): Team
    {
        $reason = trim($reason);

        $team = DB::transaction(function () use ($team, $actor, $reason): Team {
            $team->refresh();

            if ($team->isSuspended()) {
                return $team;
            }

            $team->forceFill([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ])->save();

            activity('moderation')
                ->performedOn($team)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => $reason,
                    'previous_suspended_at' => null,
                ])
                ->log('account.suspended');

            return $team->refresh();
        });

        $owner = $team->owner();

        if ($owner !== null) {
            $owner->notify(new AccountSuspended($team, $reason));
        }

        return $team;
    }
}
