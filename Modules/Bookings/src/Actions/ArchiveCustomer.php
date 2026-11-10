<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;

final class ArchiveCustomer
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(Customer $customer, User $actor, bool $archived = true): Customer
    {
        $customer->forceFill([
            'archived_at' => $archived ? now() : null,
        ])->save();

        $this->activity->handle(
            $customer,
            CustomerActivityType::System,
            $archived ? 'Cliente archivado.' : 'Cliente restaurado del archivo.',
            $actor,
        );

        return $customer->refresh();
    }
}
