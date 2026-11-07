<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScheduleHeartbeat extends Command
{
    protected $signature = 'app:schedule-heartbeat';

    protected $description = 'Record that the Laravel scheduler is running';

    public function handle(): int
    {
        Cache::forever('schedule.last_run_at', now()->toIso8601String());

        return self::SUCCESS;
    }
}
