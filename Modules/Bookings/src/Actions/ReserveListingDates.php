<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Bookings\Support\DateRange;

final class ReserveListingDates
{
    public function handle(
        BookingInquiry $inquiry,
        User $user,
        string $reason = 'Reserva aceptada',
    ): void {
        foreach (DateRange::dates(
            $inquiry->start_date->toDateString(),
            $inquiry->end_date->toDateString(),
        ) as $date) {
            $unavailability = new ListingUnavailability([
                'date' => $date,
                'status' => AvailabilityStatus::Booked,
                'reason' => $reason,
                'booking_inquiry_id' => $inquiry->id,
                'created_by' => $user->id,
            ]);
            $unavailability->listing_id = $inquiry->listing_id;
            $unavailability->save();
        }
    }
}
