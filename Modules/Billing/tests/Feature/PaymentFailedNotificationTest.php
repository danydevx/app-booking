<?php

namespace Modules\Billing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Modules\Billing\Notifications\PaymentFailed;
use Tests\TestCase;

class PaymentFailedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_invoice_notifies_only_the_affected_owner_once(): void
    {
        Notification::fake();
        config(['cashier.webhook.secret' => 'whsec_test']);

        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $team->stripe_id = 'cus_failed_test';
        $team->save();

        $payload = $this->paymentFailedPayload('evt_payment_failed_1', 'cus_failed_test');

        $this->signedWebhook($payload)->assertOk();
        $this->signedWebhook($payload)->assertOk();

        Notification::assertSentTo($owner, PaymentFailed::class, 1);
    }

    public function test_failed_invoice_for_unknown_customer_sends_nothing(): void
    {
        Notification::fake();
        config(['cashier.webhook.secret' => 'whsec_test']);

        $this->signedWebhook($this->paymentFailedPayload('evt_payment_failed_2', 'cus_missing'))->assertOk();

        Notification::assertNothingSent();
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
    private function paymentFailedPayload(string $eventId, string $customerId): array
    {
        return [
            'id' => $eventId,
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test',
                    'customer' => $customerId,
                    'status' => 'open',
                ],
            ],
        ];
    }
}
