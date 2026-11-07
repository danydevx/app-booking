<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PlatformSystemController extends Controller
{
    public function __invoke(): Response
    {
        [$failedJobsCount, $queuedJobsCount, $failedJobs, $databaseHealthy] = $this->queueSnapshot();

        return Inertia::render('Platform/System/Index', [
            'status' => [
                'environment' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'queue_connection' => (string) config('queue.default'),
                'mailer' => (string) config('mail.default'),
                'stripe_configured' => filled(config('cashier.secret')),
                'scheduler_last_run' => $this->schedulerLastRun(),
                'failed_jobs' => $failedJobsCount,
                'queued_jobs' => $queuedJobsCount,
                'database' => $databaseHealthy,
                'cache' => $this->cacheHealthy(),
                'storage' => is_writable(storage_path())
                    && is_writable(base_path('bootstrap/cache')),
            ],
            'failedJobs' => $failedJobs,
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: array<int, array<string, mixed>>, 3: bool}
     */
    private function queueSnapshot(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')
                ->latest('failed_at')
                ->limit(10)
                ->get()
                ->map(fn (object $job): array => [
                    'uuid' => (string) $job->uuid,
                    'queue' => (string) $job->queue,
                    'job' => $this->jobName((string) $job->payload),
                    'exception' => Str::limit(
                        Str::before((string) $job->exception, "\n"),
                        240,
                    ),
                    'failed_at' => Carbon::parse($job->failed_at)->toIso8601String(),
                    'retry_url' => route('platform.system.failed-jobs.retry', ['uuid' => $job->uuid]),
                    'delete_url' => route('platform.system.failed-jobs.destroy', ['uuid' => $job->uuid]),
                ])
                ->all();

            return [
                (int) DB::table('failed_jobs')->count(),
                (int) DB::table('jobs')->count(),
                $failedJobs,
                true,
            ];
        } catch (Throwable) {
            return [0, 0, [], false];
        }
    }

    private function schedulerLastRun(): ?string
    {
        try {
            $value = Cache::get('schedule.last_run_at');

            return is_string($value) ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function cacheHealthy(): bool
    {
        try {
            Cache::put('system.health', 'ok', 30);

            return Cache::get('system.health') === 'ok';
        } catch (Throwable) {
            return false;
        }
    }

    private function jobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (is_array($decoded) && is_string($decoded['displayName'] ?? null)) {
            return $decoded['displayName'];
        }

        return 'Trabajo en cola';
    }

    public function retryFailedJob(Request $request, string $uuid): RedirectResponse
    {
        abort_unless(
            DB::table('failed_jobs')->where('uuid', $uuid)->exists(),
            404,
        );

        try {
            $exitCode = Artisan::call('queue:retry', ['id' => [$uuid]]);
        } catch (Throwable) {
            $exitCode = 1;
        }

        if ($exitCode !== 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se pudo reintentar el trabajo fallido.',
            ]);

            return back();
        }

        activity('moderation')
            ->causedBy($request->user())
            ->withProperties(['uuid' => $uuid, 'actor' => $request->user()->email])
            ->log('queue.job.retried');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Trabajo devuelto a la cola.',
        ]);

        return back();
    }

    public function forgetFailedJob(Request $request, string $uuid): RedirectResponse
    {
        abort_unless(
            DB::table('failed_jobs')->where('uuid', $uuid)->exists(),
            404,
        );

        try {
            $exitCode = Artisan::call('queue:forget', ['id' => [$uuid]]);
        } catch (Throwable) {
            $exitCode = 1;
        }

        if ($exitCode !== 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'No se pudo eliminar el trabajo fallido.',
            ]);

            return back();
        }

        activity('moderation')
            ->causedBy($request->user())
            ->withProperties(['uuid' => $uuid, 'actor' => $request->user()->email])
            ->log('queue.job.forgotten');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Trabajo fallido eliminado.',
        ]);

        return back();
    }
}
