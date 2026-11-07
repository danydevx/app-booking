<?php

namespace Modules\Platform\Console\Commands;

use Illuminate\Console\Command;
use Modules\Platform\Support\PlatformAccess;

class SyncPlatformPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:sync-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update the platform role and its permissions';

    public function handle(): int
    {
        PlatformAccess::ensure();

        $this->info('Platform role and permissions synchronized.');

        return self::SUCCESS;
    }
}
