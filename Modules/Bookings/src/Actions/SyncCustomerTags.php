<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;

final class SyncCustomerTags
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    /**
     * @param  array<int, int|string>  $tagIds
     */
    public function handle(Customer $customer, User $actor, array $tagIds): void
    {
        $ids = array_map('intval', $tagIds);

        $customer->tags()->sync($ids);

        $names = $customer->tags()
            ->whereIn('customer_tags.id', $ids)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        $this->activity->handle(
            $customer,
            CustomerActivityType::System,
            $names === ''
                ? 'Etiquetas actualizadas: sin etiquetas.'
                : "Etiquetas actualizadas: {$names}.",
            $actor,
        );
    }
}
