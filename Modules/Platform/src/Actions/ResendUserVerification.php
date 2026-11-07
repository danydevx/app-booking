<?php

namespace Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class ResendUserVerification
{
    public function handle(User $user, User $actor): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'user' => 'El correo del usuario ya está verificado.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        activity('moderation')
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties([
                'email' => $user->email,
                'actor' => $actor->email,
            ])
            ->log('user.verification-resent');

        Log::notice('User verification email resent.', [
            'event' => 'user.verification-resent',
            'user_id' => $user->id,
            'email' => $user->email,
            'actor_id' => $actor->id,
        ]);
    }
}
