<?php

namespace Modules\Platform\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Notifications\AccountRestored;

final class RestoreAccount
{
    public function handle(Team $team, User $actor, string $reason): Team
    {
        $reason = trim($reason);

        $team = DB::transaction(function () use ($team, $actor, $reason): Team {
            $team->refresh();

            if (! $team->isSuspended()) {
                return $team;
            }

            $suspendedAt = $team->suspended_at;

            $team->forceFill([
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            activity('moderation')
                ->performedOn($team)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => $reason,
                    'previous_suspended_at' => $suspendedAt?->toIso8601String(),
                ])
                ->log('account.restored');

            return $team->refresh();
        });

        $owner = $team->owner();

        if ($owner !== null) {
            $owner->notify(new AccountRestored($team, $reason));
        }

        return $team;
    }
}
