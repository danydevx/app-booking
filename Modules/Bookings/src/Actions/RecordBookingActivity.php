<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Models\BookingInquiry;

final class RecordBookingActivity
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(
        BookingInquiry $inquiry,
        string $event,
        ?User $actor = null,
        array $properties = [],
    ): void {
        $logger = activity('bookings')
            ->performedOn($inquiry)
            ->withProperties($properties);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log($event);
    }
}
