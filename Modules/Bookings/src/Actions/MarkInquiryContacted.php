<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;

final class MarkInquiryContacted
{
    public function handle(BookingInquiry $inquiry): BookingInquiry
    {
        if ($inquiry->status !== InquiryStatus::New) {
            return $inquiry;
        }

        $inquiry->forceFill([
            'status' => InquiryStatus::Contacted,
            'contacted_at' => now(),
        ])->save();

        return $inquiry->refresh();
    }
}
