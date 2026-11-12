<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Mockery\MockInterface;
use Modules\Billing\Actions\CreateCheckoutSession;
use Modules\Billing\Actions\SyncTeamSubscription;
use Tests\TestCase;

class BillingSubscriptionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_success_syncs_the_subscription_when_none_exists_locally(): void
    {
        config(['cashier.secret' => 'sk_test_fake']);

        [$owner, $team] = $this->ownerWithTeam();

        $this->mock(SyncTeamSubscription::class, function (MockInterface $mock): void {
            $mock->shouldReceive('handle')->once()->andReturnNull();
        });

        $this->actingAs($owner)
            ->get(route('billing.index', ['current_team' => $team->slug, 'checkout' => 'success']))
            ->assertOk();
    }

    public function test_checkout_success_skips_the_sync_when_a_subscription_already_exists(): void
    {
        config([
            'cashier.secret' => 'sk_test_fake',
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        [$owner, $team] = $this->ownerWithTeam();

        $this->subscription($team, 'sub_existing');

        $this->mock(SyncTeamSubscription::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('handle');
        });

        $this->actingAs($owner)
            ->get(route('billing.index', ['current_team' => $team->slug, 'checkout' => 'success']))
            ->assertOk();
    }

    public function test_checkout_is_blocked_when_stripe_already_has_an_active_subscription(): void
    {
        config([
            'cashier.secret' => 'sk_test_fake',
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
        ]);

        [$owner, $team] = $this->ownerWithTeam();

        $this->mock(SyncTeamSubscription::class, function (MockInterface $mock) use ($team): void {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturnUsing(function () use ($team): void {
                    $this->subscription($team, 'sub_recovered');
                });
        });

        $this->mock(CreateCheckoutSession::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('handle');
        });

        $this->actingAs($owner)
            ->from(route('billing.index', ['current_team' => $team->slug]))
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ])
            ->assertSessionHasErrors('plan');
    }

    private function subscription(Team $team, string $stripeId): Subscription
    {
        return Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'ends_at' => null,
        ]);
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }
}
