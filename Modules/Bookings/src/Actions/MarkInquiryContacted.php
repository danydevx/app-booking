<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;

final class MarkInquiryContacted
{
    public function __construct(
        private readonly RecordBookingActivity $activity,
    ) {}

    public function handle(BookingInquiry $inquiry, ?User $actor = null): BookingInquiry
    {
        if ($inquiry->status !== InquiryStatus::New) {
            return $inquiry;
        }

        $inquiry->forceFill([
            'status' => InquiryStatus::Contacted,
            'contacted_at' => now(),
        ])->save();

        $this->activity->handle($inquiry, 'booking.contacted', $actor);

        return $inquiry->refresh();
    }
}
