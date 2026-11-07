<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Platform\Notifications\UserSuspended;
use Modules\Platform\Support\PlatformAccess;

final class SuspendUser
{
    public function handle(User $user, User $actor, string $reason): User
    {
        $reason = trim($reason);

        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'reason' => 'No puedes suspender tu propia cuenta.',
            ]);
        }

        if (PlatformAccess::isSuperadmin($user)) {
            throw ValidationException::withMessages([
                'reason' => 'No puedes suspender a otro superadmin.',
            ]);
        }

        $user = DB::transaction(function () use ($user, $actor, $reason): User {
            $user->refresh();

            if ($user->isSuspended()) {
                return $user;
            }

            $user->forceFill([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ])->save();

            activity('moderation')
                ->performedOn($user)
                ->causedBy($actor)
                ->withProperties([
                    'reason' => $reason,
                    'previous_suspended_at' => null,
                ])
                ->log('user.suspended');

            return $user->refresh();
        });

        $user->notify(new UserSuspended($reason));

        return $user;
    }
}
