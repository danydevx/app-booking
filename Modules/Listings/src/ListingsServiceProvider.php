<?php

namespace Modules\Listings;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Listings\Contracts\Geocoder;
use Modules\Listings\Models\Listing;
use Modules\Listings\Policies\ListingPolicy;
use Modules\Listings\Support\NullGeocoder;

final class ListingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Geocoder::class, NullGeocoder::class);
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if (is_dir($modulePath.'/database/migrations')) {
            $this->loadMigrationsFrom($modulePath.'/database/migrations');
        }

        if (is_dir($modulePath.'/resources/views')) {
            $this->loadViewsFrom($modulePath.'/resources/views', 'listings');
        }

        $this->loadRoutesFrom($modulePath.'/routes/web.php');

        Gate::policy(Listing::class, ListingPolicy::class);
    }
}
