<?php

namespace Modules\Billing\Listeners;

use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;
use Modules\Billing\Notifications\PaymentFailed;

final class LogStripeWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $eventId = $event->payload['id'] ?? null;
        $type = $event->payload['type'] ?? null;

        if (! is_string($eventId) || $eventId === '') {
            Log::info('Stripe webhook received without an event id.', ['type' => $type]);

            return;
        }

        $firstDelivery = Cache::add(
            "stripe:webhook:{$eventId}",
            true,
            now()->addDay(),
        );

        Log::info(
            $firstDelivery ? 'Stripe webhook received.' : 'Stripe webhook duplicate received.',
            [
                'event_id' => $eventId,
                'type' => $type,
                'duplicate' => ! $firstDelivery,
            ],
        );

        if ($firstDelivery && $type === 'invoice.payment_failed') {
            $this->notifyOwnerOfFailedPayment($event->payload);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notifyOwnerOfFailedPayment(array $payload): void
    {
        $customerId = data_get($payload, 'data.object.customer');

        if (! is_string($customerId) || $customerId === '') {
            return;
        }

        $team = Team::query()->where('stripe_id', $customerId)->first();

        if ($team === null) {
            return;
        }

        $owner = $team->owner();

        if ($owner === null) {
            return;
        }

        $owner->notify(new PaymentFailed($team));
    }
}
