<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Bookings\BookingsServiceProvider;
use Modules\Listings\ListingsServiceProvider;
use Modules\Platform\PlatformServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PlatformServiceProvider::class,
    ListingsServiceProvider::class,
    BookingsServiceProvider::class,
];
