<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;

final class AddBookingDeposit
{
    public function __construct(
        private readonly RecordBookingActivity $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        BookingInquiry $inquiry,
        array $data,
        ?User $actor = null,
    ): BookingDeposit {
        $status = DepositStatus::from((string) $data['status']);

        $deposit = $inquiry->deposits()->create([
            'amount_cents' => (int) $data['amount_cents'],
            'currency' => (string) config('bookings.deposit_currency', 'MXN'),
            'status' => $status,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'received_at' => $data['received_at']
                ?? ($status === DepositStatus::Received ? now() : null),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->activity->handle($inquiry, 'deposit.added', $actor, [
            'deposit_id' => $deposit->id,
            'amount_cents' => $deposit->amount_cents,
            'currency' => $deposit->currency,
            'status' => $deposit->status->value,
            'method' => $deposit->method->value,
            'reference' => $deposit->reference,
        ]);

        return $deposit;
    }
}
