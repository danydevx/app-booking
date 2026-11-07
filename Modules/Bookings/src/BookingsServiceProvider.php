<?php

namespace Modules\Bookings;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Bookings\Console\Commands\ExpireBookingInquiries;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Policies\BookingInquiryPolicy;
use Modules\Bookings\Policies\CustomerPolicy;

final class BookingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if (is_dir($modulePath.'/database/migrations')) {
            $this->loadMigrationsFrom($modulePath.'/database/migrations');
        }

        if (is_dir($modulePath.'/resources/views')) {
            $this->loadViewsFrom($modulePath.'/resources/views', 'bookings');
        }

        $this->loadRoutesFrom($modulePath.'/routes/web.php');

        Gate::policy(BookingInquiry::class, BookingInquiryPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireBookingInquiries::class,
            ]);
        }
    }
}
