<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckProductionReadiness extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:production-check
        {--json : Output the checks as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check production configuration and operational prerequisites';

    public function handle(): int
    {
        $checks = $this->checks();
        $failed = collect($checks)->where('status', 'FAIL')->count();
        $warnings = collect($checks)->where('status', 'WARN')->count();

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        }

        $this->table(
            ['Check', 'Status', 'Detail'],
            collect($checks)->map(fn (array $check): array => [
                $check['check'],
                $check['status'],
                $check['detail'],
            ])->all(),
        );

        $this->newLine();
        $this->line(sprintf(
            '%d checks, %d failed, %d warnings.',
            count($checks),
            $failed,
            $warnings,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array{check: string, status: string, detail: string}>
     */
    private function checks(): array
    {
        $checks = [];
        $database = (string) config('database.default');

        $add = function (string $check, bool $pass, string $detail, bool $warningOnly = false) use (&$checks): void {
            $checks[] = [
                'check' => $check,
                'status' => $pass ? 'PASS' : ($warningOnly ? 'WARN' : 'FAIL'),
                'detail' => $detail,
            ];
        };

        $add('APP_ENV', config('app.env') === 'production', 'APP_ENV='.config('app.env'));
        $add('APP_DEBUG', config('app.debug') === false, 'APP_DEBUG='.var_export(config('app.debug'), true));
        $add('APP_KEY', filled(config('app.key')), 'APP_KEY is set');
        $add('APP_URL HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL='.config('app.url'));
        $add('Database driver', in_array($database, ['mysql', 'mariadb'], true), 'driver='.$database);
        $add(
            'Database timezone',
            config("database.connections.{$database}.timezone") === '+00:00',
            'timezone='.var_export(config("database.connections.{$database}.timezone"), true),
        );
        $add('Session secure cookie', config('session.secure') === true, 'SESSION_SECURE_COOKIE='.var_export(config('session.secure'), true));
        $add('Session driver', config('session.driver') !== 'array', 'driver='.config('session.driver'));
        $add('Cache store', config('cache.default') !== 'array', 'store='.config('cache.default'));
        $add('Queue connection', config('queue.default') !== 'sync', 'connection='.config('queue.default'));
        $add('Mailer', ! in_array(config('mail.default'), ['log', 'array'], true), 'mailer='.config('mail.default'));

        try {
            DB::connection()->getPdo();
            $add('Database connection', true, 'connection established');
        } catch (Throwable $exception) {
            $add('Database connection', false, $exception->getMessage());
        }

        $add('Storage link', is_link(public_path('storage')), 'public/storage');
        $add('Storage writable', is_writable(storage_path('framework')), storage_path('framework'));
        $add('Bootstrap cache writable', is_writable(base_path('bootstrap/cache')), base_path('bootstrap/cache'));

        if (filled(config('cashier.secret'))) {
            $add('Stripe webhook secret', filled(config('cashier.webhook.secret')), 'STRIPE_WEBHOOK_SECRET');
            $add('Stripe Pro price', filled(config('billing.plans.pro.stripe_price_id')), 'STRIPE_PRICE_PRO');
        } else {
            $add('Stripe configured', false, 'STRIPE_SECRET is empty; paid plans disabled', true);
        }

        return $checks;
    }
}
