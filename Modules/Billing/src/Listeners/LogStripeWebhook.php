<?php

namespace Modules\Billing\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

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
    }
}
