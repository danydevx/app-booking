<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_subscription_webhook_activates_pro_entitlements(): void
    {
        config([
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
            'cashier.webhook.secret' => 'whsec_test',
        ]);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_test_1';
        $team->save();

        $payload = $this->subscriptionCreatedPayload('evt_test_1', 'sub_test_1', 'cus_test_1');

        $this->signedWebhook($payload)->assertOk();

        $this->assertSame(1, Subscription::query()->where('team_id', $team->id)->count());
        $this->assertDatabaseHas('subscriptions', [
            'team_id' => $team->id,
            'stripe_id' => 'sub_test_1',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
        ]);

        $entitlements = app(Entitlements::class);
        $freshTeam = Team::query()->findOrFail($team->id);

        $this->assertSame('pro', $entitlements->planFor($freshTeam)->slug);
        $this->assertTrue($entitlements->allows($freshTeam, 'customers'));
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        config(['cashier.webhook.secret' => 'whsec_test']);

        $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid',
            ],
            json_encode($this->subscriptionCreatedPayload('evt_test_bad', 'sub_test_bad', 'cus_test_bad')),
        )->assertForbidden();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_duplicate_webhook_delivery_is_idempotent(): void
    {
        config([
            'billing.plans.pro.stripe_price_id' => 'price_pro_test',
            'cashier.webhook.secret' => 'whsec_test',
        ]);

        $team = Team::factory()->create();
        $team->stripe_id = 'cus_test_2';
        $team->save();

        $payload = $this->subscriptionCreatedPayload('evt_test_2', 'sub_test_2', 'cus_test_2');

        $this->signedWebhook($payload)->assertOk();
        $this->signedWebhook($payload)->assertOk();

        $this->assertSame(1, Subscription::query()->where('team_id', $team->id)->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signedWebhook(array $payload): TestResponse
    {
        $content = (string) json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$content, 'whsec_test');

        return $this->call(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $content,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionCreatedPayload(string $eventId, string $subscriptionId, string $customerId): array
    {
        return [
            'id' => $eventId,
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => $subscriptionId,
                    'customer' => $customerId,
                    'status' => 'active',
                    'metadata' => [
                        'type' => 'default',
                        'name' => 'default',
                    ],
                    'items' => [
                        'data' => [
                            [
                                'id' => 'si_'.$subscriptionId,
                                'quantity' => 1,
                                'price' => [
                                    'id' => 'price_pro_test',
                                    'product' => 'prod_test',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
