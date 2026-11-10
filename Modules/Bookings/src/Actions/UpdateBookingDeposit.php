<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Models\BookingDeposit;

final class UpdateBookingDeposit
{
    public function __construct(
        private readonly RecordBookingActivity $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        BookingDeposit $deposit,
        array $data,
        ?User $actor = null,
    ): BookingDeposit {
        $oldStatus = $deposit->status->value;
        $oldAmountCents = $deposit->amount_cents;

        $deposit->update($data);

        if ($deposit->status === DepositStatus::Received && $deposit->received_at === null) {
            $deposit->update(['received_at' => now()]);
        }

        $deposit->refresh();
        $deposit->loadMissing('inquiry');

        $this->activity->handle($deposit->inquiry, 'deposit.updated', $actor, [
            'deposit_id' => $deposit->id,
            'old_status' => $oldStatus,
            'status' => $deposit->status->value,
            'old_amount_cents' => $oldAmountCents,
            'amount_cents' => $deposit->amount_cents,
            'currency' => $deposit->currency,
            'reference' => $deposit->reference,
        ]);

        return $deposit;
    }
}
