<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_review_moderation_activity(): void
    {
        $admin = $this->superadmin();

        activity('moderation')
            ->performedOn($admin)
            ->causedBy($admin)
            ->withProperties(['reason' => 'Prueba de auditoría'])
            ->log('user.suspended');

        $this->actingAs($admin)
            ->get(route('platform.activity.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Activity/Index')
                ->has('activities.data', 1)
                ->where('activities.data.0.description', 'user.suspended'));
    }

    public function test_normal_users_cannot_review_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.activity.index'))
            ->assertForbidden();
    }

    private function superadmin(): User
    {
        PlatformAccess::ensure();

        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ROLE);

        return $user;
    }
}
