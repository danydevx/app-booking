<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;

final class RecordCustomerActivity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        Customer $customer,
        CustomerActivityType $type,
        string $body,
        ?User $actor = null,
        ?string $occurredAt = null,
        array $metadata = [],
    ): CustomerActivity {
        $activity = new CustomerActivity([
            'type' => $type,
            'body' => trim($body),
            'occurred_at' => $occurredAt
                ? CarbonImmutable::parse($occurredAt)
                : now(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
        $activity->team_id = $customer->team_id;
        $activity->customer_id = $customer->id;
        $activity->user_id = $actor?->id;
        $activity->save();

        return $activity->refresh();
    }
}
