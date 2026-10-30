<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_without_subscription_uses_the_free_plan(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $entitlements = app(Entitlements::class);

        $plan = $entitlements->planFor($team);

        $this->assertSame('free', $plan->slug);
        $this->assertSame(1, $entitlements->limit($team, 'listings'));
        $this->assertSame(5, $entitlements->limit($team, 'photos_per_listing'));
        $this->assertSame(0, $entitlements->limit($team, 'videos_per_listing'));
        $this->assertFalse($entitlements->allows($team, 'customers'));
        $this->assertTrue($entitlements->canCreateListing($team, 0));
        $this->assertFalse($entitlements->canCreateListing($team, 1));
    }

    public function test_an_active_pro_subscription_unlocks_pro_limits(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $this->subscribe($team, 'price_pro_test', 'active');

        $entitlements = app(Entitlements::class);

        $this->assertSame('pro', $entitlements->planFor($team)->slug);
        $this->assertSame(5, $entitlements->limit($team, 'listings'));
        $this->assertSame(20, $entitlements->limit($team, 'photos_per_listing'));
        $this->assertSame(3, $entitlements->limit($team, 'videos_per_listing'));
        $this->assertTrue($entitlements->allows($team, 'customers'));
        $this->assertFalse($entitlements->allows($team, 'ai_posts'));
        $this->assertTrue($entitlements->canUpload($team, 'image', 19));
        $this->assertFalse($entitlements->canUpload($team, 'image', 20));
    }

    public function test_past_due_keeps_pro_during_the_grace_window_only(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);
        config(['billing.past_due_grace_days' => 3]);

        $team = Team::factory()->create();
        $subscription = $this->subscribe($team, 'price_pro_test', 'past_due');

        $entitlements = app(Entitlements::class);

        $this->assertSame('past_due_grace', $entitlements->statusFor($team));
        $this->assertSame('pro', $entitlements->planFor($team)->slug);

        DB::table('subscriptions')->where('id', $subscription->id)->update([
            'updated_at' => now()->subDays(5),
        ]);

        $this->assertSame('past_due_locked', $entitlements->statusFor($team));
        $this->assertSame('free', $entitlements->planFor($team)->slug);
    }

    public function test_canceled_subscription_keeps_pro_until_the_period_ends(): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        $team = Team::factory()->create();
        $subscription = $this->subscribe($team, 'price_pro_test', 'active', now()->addDays(10));

        $entitlements = app(Entitlements::class);

        $this->assertSame('canceling', $entitlements->statusFor($team));
        $this->assertSame('pro', $entitlements->planFor($team)->slug);

        DB::table('subscriptions')->where('id', $subscription->id)->update([
            'ends_at' => now()->subDay(),
        ]);

        $this->assertSame('free', $entitlements->planFor($team)->slug);
    }

    public function test_unknown_price_or_unknown_feature_falls_back_to_free(): void
    {
        $team = Team::factory()->create();
        $this->subscribe($team, 'price_unknown', 'active');

        $entitlements = app(Entitlements::class);

        $this->assertSame('free', $entitlements->planFor($team)->slug);
        $this->assertFalse($entitlements->allows($team, 'does_not_exist'));
    }

    private function subscribe(
        Team $team,
        string $priceId,
        string $status,
        ?\DateTimeInterface $endsAt = null,
    ): Subscription {
        return Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => $status,
            'stripe_price' => $priceId,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => $endsAt,
        ]);
    }
}
