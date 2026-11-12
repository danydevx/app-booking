<?php

namespace Modules\Billing\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Actions\SyncTeamSubscription;
use Modules\Billing\Support\Entitlements;
use Throwable;

class SyncStripeSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:sync-subscription
        {team? : Slug of the team to synchronize}
        {--all : Synchronize every team with a Stripe customer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mirror Stripe subscriptions locally when webhooks were missed';

    public function handle(SyncTeamSubscription $sync, Entitlements $entitlements): int
    {
        $teams = $this->teams();

        if ($teams->isEmpty()) {
            $this->warn('No teams to synchronize.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($teams as $team) {
            try {
                $subscription = $sync->handle($team);

                if (! $subscription) {
                    $this->line("{$team->slug}: no Stripe subscription found.");

                    continue;
                }

                $plan = $entitlements->planFor($team->fresh());

                $this->info("{$team->slug}: {$subscription->stripe_status} ({$plan->slug}).");
            } catch (Throwable $exception) {
                $failures++;

                report($exception);

                $this->error("{$team->slug}: {$exception->getMessage()}");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return Collection<int, Team>
     */
    private function teams(): Collection
    {
        if ($this->option('all')) {
            return Team::query()->whereNotNull('stripe_id')->orderBy('id')->get();
        }

        $slug = $this->argument('team');

        if (! is_string($slug) || $slug === '') {
            $this->error('Provide a team slug or use --all.');

            return Team::query()->whereRaw('1 = 0')->get();
        }

        return Team::query()->where('slug', $slug)->get();
    }
}
