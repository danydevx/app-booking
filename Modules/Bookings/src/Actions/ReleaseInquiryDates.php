<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\ListingUnavailability;

final class ReleaseInquiryDates
{
    public function handle(BookingInquiry $inquiry): void
    {
        ListingUnavailability::query()
            ->where('booking_inquiry_id', $inquiry->id)
            ->delete();
    }
}
