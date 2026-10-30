<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;

final class AddBookingDeposit
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BookingInquiry $inquiry, array $data): BookingDeposit
    {
        $status = DepositStatus::from((string) $data['status']);

        return $inquiry->deposits()->create([
            'amount_cents' => (int) $data['amount_cents'],
            'currency' => (string) config('bookings.deposit_currency', 'MXN'),
            'status' => $status,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'received_at' => $data['received_at']
                ?? ($status === DepositStatus::Received ? now() : null),
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
