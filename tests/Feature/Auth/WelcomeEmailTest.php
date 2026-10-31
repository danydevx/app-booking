<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_a_queued_welcome_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nueva Anfitriona',
            'email' => 'nueva@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $user = User::query()->where('email', 'nueva@example.test')->firstOrFail();

        Notification::assertSentTo($user, WelcomeNotification::class, function (WelcomeNotification $notification, array $channels): bool {
            return $notification instanceof ShouldQueue
                && in_array('mail', $channels, true);
        });
    }
}
