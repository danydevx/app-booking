<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Platform\Notifications\UserRestored;

final class RestoreUser
{
    public function handle(User $user, User $actor, string $reason): User
    {
        $reason = trim($reason);

        $user = DB::transaction(function () use ($user, $actor, $reason): User {
            $user->refresh();

            if (! $user->isSuspended()) {
                return $user;
            }

            $suspendedAt = $user->suspended_at;

            $user->forceFill([
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            activity('moderation')
                ->performedOn($user)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => $reason,
                    'previous_suspended_at' => $suspendedAt?->toIso8601String(),
                ])
                ->log('user.restored');

            return $user->refresh();
        });

        $user->notify(new UserRestored($reason));

        return $user;
    }
}
