<?php

namespace Modules\Platform\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class PlatformRoleManager
{
    public function grant(User $user, User $actor): void
    {
        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'user' => 'El usuario debe verificar su correo antes de recibir acceso de plataforma.',
            ]);
        }

        PlatformAccess::ensure();

        if ($user->hasRole(PlatformAccess::ROLE)) {
            return;
        }

        $user->assignRole(PlatformAccess::ROLE);

        activity('moderation')
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties([
                'email' => $user->email,
                'action' => 'grant',
                'actor' => $actor->email,
            ])
            ->log('platform.superadmin.granted');

        Log::notice('Platform superadmin access granted.', [
            'event' => 'platform.superadmin.granted',
            'user_id' => $user->id,
            'email' => $user->email,
            'actor_id' => $actor->id,
        ]);
    }

    public function revoke(User $user, User $actor): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'No puedes revocar tu propio acceso de superadmin.',
            ]);
        }

        if (! $user->hasRole(PlatformAccess::ROLE)) {
            return;
        }

        if ($this->superadminCount() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Debe existir al menos un superadmin en la plataforma.',
            ]);
        }

        $user->removeRole(PlatformAccess::ROLE);

        activity('moderation')
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties([
                'email' => $user->email,
                'action' => 'revoke',
                'actor' => $actor->email,
            ])
            ->log('platform.superadmin.revoked');

        Log::notice('Platform superadmin access revoked.', [
            'event' => 'platform.superadmin.revoked',
            'user_id' => $user->id,
            'email' => $user->email,
            'actor_id' => $actor->id,
        ]);
    }

    public function superadminCount(): int
    {
        return User::role(PlatformAccess::ROLE)->count();
    }
}
