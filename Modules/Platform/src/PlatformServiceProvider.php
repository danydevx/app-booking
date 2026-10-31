<?php

namespace Modules\Platform;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Platform\Console\Commands\MakePlatformSuperadmin;
use Modules\Platform\Console\Commands\RevokePlatformSuperadmin;

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

        RateLimiter::for('listing-reports', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip()));

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakePlatformSuperadmin::class,
                RevokePlatformSuperadmin::class,
            ]);
        }
    }
}
