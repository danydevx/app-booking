<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Models\CustomerActivity;

final class CustomerActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(CustomerActivity $activity, bool $canDelete): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type->value,
            'type_label' => $activity->type->label(),
            'body' => $activity->body,
            'author' => $activity->user?->name,
            'occurred_at' => $activity->occurred_at->toIso8601String(),
            'can_delete' => $canDelete,
            'delete_url' => route('bookings.customers.activities.destroy', [
                'current_team' => $activity->team->slug,
                'customer' => $activity->customer_id,
                'activity' => $activity->id,
            ]),
        ];
    }
}
