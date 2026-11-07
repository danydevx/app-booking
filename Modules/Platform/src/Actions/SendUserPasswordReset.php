<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class SendUserPasswordReset
{
    public function handle(User $user, User $actor): void
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'user' => (string) __($status),
            ]);
        }

        activity('moderation')
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties([
                'email' => $user->email,
                'actor' => $actor->email,
            ])
            ->log('user.password-reset-sent');

        Log::notice('User password reset email sent.', [
            'event' => 'user.password-reset-sent',
            'user_id' => $user->id,
            'email' => $user->email,
            'actor_id' => $actor->id,
        ]);
    }
}
