<?php

namespace Modules\Bookings\Actions;

use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;

final class ExpireStaleInquiries
{
    /**
     * Expire new/contacted inquiries whose requested dates have passed.
     */
    public function handle(bool $dryRun = false): int
    {
        $graceDays = max(0, (int) config('bookings.expire_after_end_days', 0));
        $expired = 0;

        BookingInquiry::query()
            ->with('listing')
            ->whereIn('status', [InquiryStatus::New, InquiryStatus::Contacted])
            ->where('end_date', '<=', now()->addDays(2)->toDateString())
            ->chunkById(100, function ($inquiries) use ($graceDays, $dryRun, &$expired): void {
                foreach ($inquiries as $inquiry) {
                    $timezone = $inquiry->listing->timezone;
                    $cutoff = now($timezone)->subDays($graceDays)->toDateString();

                    if ($inquiry->end_date->toDateString() >= $cutoff) {
                        continue;
                    }

                    $expired++;

                    if ($dryRun) {
                        continue;
                    }

                    $inquiry->forceFill([
                        'status' => InquiryStatus::Expired,
                        'expired_at' => now(),
                    ])->save();
                }
            });

        return $expired;
    }
}
