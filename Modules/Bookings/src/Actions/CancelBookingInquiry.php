<?php

namespace Modules\Bookings\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;

final class CancelBookingInquiry
{
    public function __construct(
        private readonly ReleaseInquiryDates $releaseDates,
    ) {}

    public function handle(BookingInquiry $inquiry): BookingInquiry
    {
        return DB::transaction(function () use ($inquiry): BookingInquiry {
            $locked = BookingInquiry::query()
                ->whereKey($inquiry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === InquiryStatus::Cancelled) {
                return $locked;
            }

            $this->releaseDates->handle($locked);

            $locked->forceFill([
                'status' => InquiryStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }
}
