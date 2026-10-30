<?php

namespace Modules\Bookings\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\InquiryDeclinedVisitorNotification;

final class DeclineBookingInquiry
{
    public function __construct(
        private readonly ReleaseInquiryDates $releaseDates,
    ) {}

    public function handle(BookingInquiry $inquiry): BookingInquiry
    {
        $declinedNow = false;

        $declined = DB::transaction(function () use ($inquiry, &$declinedNow): BookingInquiry {
            $locked = BookingInquiry::query()
                ->whereKey($inquiry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === InquiryStatus::Declined) {
                return $locked;
            }

            $this->releaseDates->handle($locked);

            $locked->forceFill([
                'status' => InquiryStatus::Declined,
                'declined_at' => now(),
            ])->save();

            $declinedNow = true;

            return $locked->refresh();
        });

        if ($declinedNow) {
            $declined->loadMissing('listing');

            Notification::route('mail', $declined->email)
                ->notify(new InquiryDeclinedVisitorNotification($declined));
        }

        return $declined;
    }
}
