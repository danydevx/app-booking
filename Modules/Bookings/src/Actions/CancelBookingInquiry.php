<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;

final class CancelBookingInquiry
{
    public function __construct(
        private readonly ReleaseInquiryDates $releaseDates,
        private readonly RecordBookingActivity $activity,
    ) {}

    public function handle(BookingInquiry $inquiry, ?User $actor = null): BookingInquiry
    {
        $cancelledNow = false;

        $cancelled = DB::transaction(function () use ($inquiry, &$cancelledNow): BookingInquiry {
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

            $cancelledNow = true;

            return $locked->refresh();
        });

        if ($cancelledNow) {
            $this->activity->handle($cancelled, 'booking.cancelled', $actor, [
                'start_date' => $cancelled->start_date->toDateString(),
                'end_date' => $cancelled->end_date->toDateString(),
            ]);
        }

        return $cancelled;
    }
}
