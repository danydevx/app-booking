<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class PlatformSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_review_system_status_and_failed_jobs(): void
    {
        $admin = $this->superadmin();
        Cache::forever('schedule.last_run_at', '2026-11-07T10:00:00+00:00');
        $this->failedJob('11111111-1111-1111-1111-111111111111');

        $this->actingAs($admin)
            ->get(route('platform.system.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/System/Index')
                ->where('status.failed_jobs', 1)
                ->where('status.scheduler_last_run', '2026-11-07T10:00:00+00:00')
                ->where('status.database', true)
                ->has('failedJobs', 1)
                ->where('failedJobs.0.job', 'App\\Jobs\\SendThing')
                ->where('failedJobs.0.exception', 'RuntimeException: boom'));
    }

    public function test_normal_users_cannot_access_system_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('platform.system.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_forget_a_failed_job(): void
    {
        $admin = $this->superadmin();
        $uuid = '22222222-2222-2222-2222-222222222222';
        $this->failedJob($uuid);

        $this->actingAs($admin)
            ->delete(route('platform.system.failed-jobs.destroy', ['uuid' => $uuid]))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'queue.job.forgotten',
        ]);
    }

    public function test_superadmin_can_retry_a_failed_job(): void
    {
        config(['queue.default' => 'database']);

        $admin = $this->superadmin();
        $uuid = '33333333-3333-3333-3333-333333333333';
        $this->failedJob($uuid);

        $this->actingAs($admin)
            ->post(route('platform.system.failed-jobs.retry', ['uuid' => $uuid]))
            ->assertRedirect();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'moderation',
            'description' => 'queue.job.retried',
        ]);
    }

    public function test_scheduler_heartbeat_is_recorded(): void
    {
        $this->artisan('app:schedule-heartbeat')->assertSuccessful();

        $this->assertNotNull(Cache::get('schedule.last_run_at'));
    }

    private function failedJob(string $uuid): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\SendThing',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => null,
                'timeout' => null,
                'data' => [],
            ]),
            'exception' => "RuntimeException: boom\n#0 /app/path",
            'failed_at' => Carbon::now(),
        ]);
    }

    private function superadmin(): User
    {
        PlatformAccess::ensure();

        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ROLE);

        return $user;
    }
}
