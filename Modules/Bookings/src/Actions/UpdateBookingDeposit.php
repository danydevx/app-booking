<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;

final class UpdateBookingDeposit
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BookingDeposit $deposit, array $data): BookingDeposit
    {
        $deposit->update($data);

        if ($deposit->status === DepositStatus::Received && $deposit->received_at === null) {
            $deposit->update(['received_at' => now()]);
        }

        return $deposit->refresh();
    }
}
