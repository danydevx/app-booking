<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\UpcomingBookingReminder;

final class SendUpcomingBookingReminders
{
    public function handle(bool $dryRun = false): int
    {
        $days = max(0, (int) config('bookings.reminder_days', 3));
        $sent = 0;

        BookingInquiry::query()
            ->with(['listing.team', 'customer'])
            ->where('status', InquiryStatus::Accepted)
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_date', [
                now()->subDay()->toDateString(),
                now()->addDays($days + 2)->toDateString(),
            ])
            ->chunkById(100, function ($inquiries) use ($days, $dryRun, &$sent): void {
                foreach ($inquiries as $inquiry) {
                    $timezone = $inquiry->listing->timezone;
                    $target = now($timezone)->addDays($days)->toDateString();

                    if ($inquiry->start_date->toDateString() !== $target) {
                        continue;
                    }

                    $owner = $inquiry->listing->team?->owner();

                    if ($owner === null) {
                        continue;
                    }

                    $sent++;

                    if ($dryRun) {
                        continue;
                    }

                    $owner->notify(new UpcomingBookingReminder($inquiry));

                    $inquiry->forceFill(['reminder_sent_at' => now()])->save();
                }
            });

        return $sent;
    }
}
