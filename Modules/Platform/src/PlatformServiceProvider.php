<?php

namespace Modules\Platform;

use Illuminate\Support\ServiceProvider;
use Modules\Platform\Console\Commands\MakePlatformSuperadmin;

final class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if (is_dir($modulePath.'/database/migrations')) {
            $this->loadMigrationsFrom($modulePath.'/database/migrations');
        }

        if (is_dir($modulePath.'/resources/views')) {
            $this->loadViewsFrom($modulePath.'/resources/views', 'platform');
        }

        $this->loadRoutesFrom($modulePath.'/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePlatformSuperadmin::class,
            ]);
        }
    }
}
