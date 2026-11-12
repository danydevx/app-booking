<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Modules\Billing\Actions\CreateCheckoutSession;
use Tests\TestCase;

class BillingCheckoutRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_checkout_uses_location_header_for_stripe(): void
    {
        config([
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
            'cashier.secret' => 'sk_test_fake',
        ]);

        [$owner, $team] = $this->ownerWithTeam();
        $checkoutUrl = 'https://checkout.stripe.com/c/pay/test';

        $this->mock(CreateCheckoutSession::class, function (MockInterface $mock) use ($checkoutUrl): void {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturn($checkoutUrl);
        });

        $response = $this->actingAs($owner)
            ->withHeader('X-Inertia', 'true')
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ]);

        $response->assertStatus(409);
        $this->assertSame(
            $checkoutUrl,
            $response->headers->get('X-Inertia-Location'),
        );
    }

    public function test_non_inertia_checkout_redirects_to_stripe(): void
    {
        config([
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
            'cashier.secret' => 'sk_test_fake',
        ]);

        [$owner, $team] = $this->ownerWithTeam();
        $checkoutUrl = 'https://checkout.stripe.com/c/pay/test';

        $this->mock(CreateCheckoutSession::class, function (MockInterface $mock) use ($checkoutUrl): void {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturn($checkoutUrl);
        });

        $this->actingAs($owner)
            ->post(route('billing.checkout', ['current_team' => $team->slug]), [
                'plan' => 'pro',
            ])
            ->assertRedirect($checkoutUrl);
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
