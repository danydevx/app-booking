<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_superadmin_can_filter_activity_by_actor_and_date(): void
    {
        $admin = $this->superadmin();
        $other = User::factory()->create([
            'name' => 'Actor Secundario',
            'email' => 'actor@example.test',
        ]);

        $old = activity('moderation')
            ->causedBy($other)
            ->log('user.old-event');

        DB::table('activity_log')
            ->where('id', $old->id)
            ->update(['created_at' => now()->subDays(10)]);

        activity('moderation')
            ->causedBy($admin)
            ->log('user.recent-event');

        $this->actingAs($admin)
            ->get(route('platform.activity.index', ['actor' => 'actor@example.test']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.description', 'user.old-event')
                ->where('filters.actor', 'actor@example.test'));

        $this->actingAs($admin)
            ->get(route('platform.activity.index', [
                'from' => now()->subDays(5)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.description', 'user.recent-event'));
    }

    public function test_invalid_activity_dates_are_rejected(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->get(route('platform.activity.index', [
                'from' => 'no-es-fecha',
            ]))
            ->assertSessionHasErrors('from');
    }

    public function test_superadmin_can_export_filtered_audit_csv(): void
    {
        $admin = $this->superadmin();
        $other = User::factory()->create([
            'name' => 'Actor Export',
            'email' => 'export-actor@example.test',
        ]);

        activity('moderation')
            ->causedBy($other)
            ->withProperties(['reason' => 'Exportar este motivo'])
            ->log('user.exported');

        activity('moderation')
            ->causedBy($admin)
            ->withProperties(['reason' => 'No debe exportarse'])
            ->log('user.other');

        $response = $this->actingAs($admin)->get(
            route('platform.activity.export', ['actor' => 'export-actor@example.test']),
        );

        $response->assertOk()->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString('user.exported', $content);
        $this->assertStringContainsString('Exportar este motivo', $content);
        $this->assertStringNotContainsString('user.other', $content);
    }

    public function test_normal_users_cannot_review_or_export_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.activity.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('platform.activity.export'))
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
