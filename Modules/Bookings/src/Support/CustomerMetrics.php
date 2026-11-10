<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;

final class CustomerMetrics
{
    /**
     * @return array{
     *     total_inquiries: int,
     *     accepted_inquiries: int,
     *     upcoming_bookings: int,
     *     deposits_received_cents: int,
     *     last_activity_at: string|null,
     *     next_task: CustomerTask|null
     * }
     */
    public function for(Customer $customer): array
    {
        $inquiries = $customer->inquiries();
        $timezone = $customer->team->timezone;

        $nextTask = $customer->tasks()
            ->open()
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->first();

        return [
            'total_inquiries' => (clone $inquiries)->count(),
            'accepted_inquiries' => (clone $inquiries)
                ->where('status', InquiryStatus::Accepted)
                ->count(),
            'upcoming_bookings' => (clone $inquiries)
                ->where('status', InquiryStatus::Accepted)
                ->where('start_date', '>=', now($timezone)->toDateString())
                ->count(),
            'deposits_received_cents' => (int) BookingDeposit::query()
                ->whereHas('inquiry', fn ($query) => $query->where('customer_id', $customer->id))
                ->where('status', DepositStatus::Received)
                ->sum('amount_cents'),
            'last_activity_at' => $customer->activities()
                ->latest('occurred_at')
                ->value('occurred_at'),
            'next_task' => $nextTask,
        ];
    }
}
