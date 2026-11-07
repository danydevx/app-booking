<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Platform\Notifications\UserRestored;
use Modules\Platform\Notifications\UserSuspended;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_view_registered_users(): void
    {
        $admin = $this->superadmin();
        $target = User::factory()->create(['name' => 'Target User']);

        $this->actingAs($admin)
            ->get(route('platform.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Users/Index')
                ->has('users.data', 2));

        $this->actingAs($admin)
            ->get(route('platform.users.index', ['q' => 'Target']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', $target->email));

        $this->actingAs($admin)
            ->get(route('platform.users.show', ['user' => $target->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Users/Show')
                ->where('user.email', $target->email)
                ->where('user.is_suspended', false));
    }

    public function test_normal_users_cannot_access_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.users.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_suspend_and_restore_a_user_with_audit(): void
    {
        Notification::fake();

        $admin = $this->superadmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('platform.users.suspend', ['user' => $target->id]), [
                'reason' => 'Actividad fraudulenta.',
            ])
            ->assertRedirect();

        $target->refresh();

        $this->assertTrue($target->isSuspended());
        $this->assertSame('Actividad fraudulenta.', $target->suspension_reason);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'user.suspended',
            'subject_id' => $target->id,
        ]);
        Notification::assertSentTo($target, UserSuspended::class);

        $this->actingAs($target)
            ->get('/')
            ->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($admin)
            ->post(route('platform.users.restore', ['user' => $target->id]), [
                'reason' => 'Revisión completada.',
            ])
            ->assertRedirect();

        $target->refresh();

        $this->assertFalse($target->isSuspended());
        $this->assertNull($target->suspension_reason);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'user.restored',
            'subject_id' => $target->id,
        ]);
        Notification::assertSentTo($target, UserRestored::class);

        $this->actingAs($target)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_superadmin_cannot_suspend_self_or_another_superadmin(): void
    {
        $admin = $this->superadmin();
        $other = $this->superadmin();

        $this->actingAs($admin)
            ->post(route('platform.users.suspend', ['user' => $admin->id]), [
                'reason' => 'Intento inválido.',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($admin->fresh()->isSuspended());

        $this->actingAs($admin)
            ->post(route('platform.users.suspend', ['user' => $other->id]), [
                'reason' => 'Intento inválido.',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($other->fresh()->isSuspended());
    }

    private function superadmin(): User
    {
        PlatformAccess::ensure();

        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ROLE);

        return $user;
    }
}
