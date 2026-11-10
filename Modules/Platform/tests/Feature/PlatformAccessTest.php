<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Platform\Support\PlatformAccess;
use Modules\Platform\Support\PlatformPermission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_platform_area(): void
    {
        $this->get('/app/platform')->assertRedirect('/login');
    }

    public function test_normal_users_cannot_access_the_platform_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/app/platform')->assertForbidden();
    }

    public function test_command_promotes_an_existing_verified_user_idempotently(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);

        $this->artisan('app:make-platform-superadmin', [
            'email' => 'OWNER@example.test',
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('app:make-platform-superadmin', [
            'email' => 'owner@example.test',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Role::query()->where('name', PlatformAccess::ROLE)->count());
        $this->assertTrue($user->fresh()->hasRole(PlatformAccess::ROLE));
        $this->assertSame(1, $user->fresh()->roles()->count());

        foreach (PlatformPermission::all() as $permission) {
            $this->assertTrue($user->fresh()->hasPermissionTo($permission));
        }
    }

    public function test_command_rejects_unverified_users(): void
    {
        User::factory()->unverified()->create(['email' => 'unverified@example.test']);

        $this->artisan('app:make-platform-superadmin', [
            'email' => 'unverified@example.test',
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('roles', ['name' => PlatformAccess::ROLE]);
    }

    public function test_command_rejects_unknown_users(): void
    {
        $this->artisan('app:make-platform-superadmin', [
            'email' => 'missing@example.test',
            '--force' => true,
        ])->assertExitCode(1);
    }

    public function test_superadmin_can_open_the_platform_dashboard(): void
    {
        $user = User::factory()->create();
        Team::factory()->create();

        $this->artisan('app:make-platform-superadmin', [
            'email' => $user->email,
            '--force' => true,
        ])->assertSuccessful();

        $this->actingAs($user->fresh())
            ->get('/app/platform')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Dashboard')
                ->where('platform.isSuperadmin', true)
                ->where('stats.accounts', 2)
                ->where('stats.users', 1));
    }

    public function test_public_registration_never_assigns_the_platform_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Owner',
            'email' => 'new-owner@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();

        $user = User::query()->where('email', 'new-owner@example.test')->firstOrFail();

        $this->assertFalse($user->hasRole(PlatformAccess::ROLE));
    }
}
