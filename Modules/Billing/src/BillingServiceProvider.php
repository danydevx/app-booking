<?php

namespace Modules\Billing;

use App\Models\Team;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Modules\Billing\Listeners\LogStripeWebhook;
use Modules\Billing\Policies\BillingPolicy;

final class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Cashier::useCustomerModel(Team::class);
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if (is_dir($modulePath.'/database/migrations')) {
            $this->loadMigrationsFrom($modulePath.'/database/migrations');
        }

        $this->loadRoutesFrom($modulePath.'/routes/web.php');

        Gate::define('manageBilling', [BillingPolicy::class, 'manage']);

        Event::listen(WebhookReceived::class, LogStripeWebhook::class);
    }
}
