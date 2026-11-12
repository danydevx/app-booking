<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Modules\Billing\Actions\SyncTeamSubscription;
use Modules\Billing\Support\Entitlements;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class SyncTeamSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_mirrors_a_paid_stripe_subscription_locally(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_sync_test';
        $team->save();

        $subscription = app(SyncTeamSubscription::class)->apply($team, [
            $this->stripeSubscription('sub_sync_1', 'price_pro_test', 1700000000),
        ]);

        $this->assertNotNull($subscription);
        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $team->id,
            'stripe_id' => 'sub_sync_1',
            'type' => 'default',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
        ]);
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'stripe_id' => 'si_sync_1',
            'stripe_price' => 'price_pro_test',
        ]);

        $entitlements = app(Entitlements::class);
        $this->assertSame('pro', $entitlements->planFor($team->fresh())->slug);
    }

    public function test_it_is_idempotent_when_the_same_subscription_is_synced_twice(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_sync_test';
        $team->save();

        $action = app(SyncTeamSubscription::class);
        $action->apply($team, [$this->stripeSubscription('sub_sync_1', 'price_pro_test', 1700000000)]);
        $action->apply($team, [$this->stripeSubscription('sub_sync_1', 'price_pro_test', 1700000000)]);

        $this->assertSame(1, Subscription::query()->where('team_id', $team->id)->count());
        $this->assertSame(1, $team->subscriptions()->first()?->items()->count());
    }

    public function test_it_keeps_an_already_mirrored_subscription_over_a_newer_one(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_sync_test';
        $team->save();

        $action = app(SyncTeamSubscription::class);

        $action->apply($team, [$this->stripeSubscription('sub_old', 'price_pro_test', 1700000000)]);
        $action->apply($team, [
            $this->stripeSubscription('sub_new', 'price_pro_other', 1700009999),
            $this->stripeSubscription('sub_old', 'price_pro_test', 1700000000),
        ]);

        $this->assertSame(1, Subscription::query()->where('team_id', $team->id)->count());
        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $team->id,
            'stripe_id' => 'sub_old',
        ]);
    }

    public function test_a_canceled_subscription_keeps_the_team_on_the_free_plan(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_sync_test';
        $team->save();

        app(SyncTeamSubscription::class)->apply($team, [
            $this->stripeSubscription('sub_canceled', 'price_pro_test', 1700000000, [
                'status' => 'canceled',
                'canceled_at' => 1700005000,
            ]),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $team->id,
            'stripe_id' => 'sub_canceled',
            'stripe_status' => 'canceled',
        ]);

        $this->assertSame('free', app(Entitlements::class)->planFor($team->fresh())->slug);
    }

    public function test_it_returns_null_without_a_stripe_customer(): void
    {
        $team = Team::factory()->create();

        $this->assertNull(app(SyncTeamSubscription::class)->handle($team));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function stripeSubscription(
        string $id,
        string $priceId,
        int $created,
        array $overrides = [],
    ): StripeSubscription {
        return StripeSubscription::constructFrom([
            'id' => $id,
            'customer' => 'cus_sync_test',
            'status' => 'active',
            'created' => $created,
            'cancel_at_period_end' => false,
            'metadata' => ['type' => 'default'],
            'items' => [
                'data' => [
                    [
                        'id' => 'si_sync_1',
                        'quantity' => 1,
                        'current_period_end' => $created + 2592000,
                        'price' => [
                            'id' => $priceId,
                            'product' => 'prod_test',
                        ],
                    ],
                ],
            ],
            ...$overrides,
        ]);
    }
}
