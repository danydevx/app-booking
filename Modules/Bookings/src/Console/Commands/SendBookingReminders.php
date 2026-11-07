<?php

namespace Modules\Bookings\Console\Commands;

use Illuminate\Console\Command;
use Modules\Bookings\Actions\SendUpcomingBookingReminders;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders
        {--dry-run : Report reminders without sending them}';

    protected $description = 'Send reminders for accepted bookings starting soon';

    public function handle(SendUpcomingBookingReminders $sendReminders): int
    {
        $count = $sendReminders->handle((bool) $this->option('dry-run'));

        $this->info($this->option('dry-run')
            ? "{$count} reminders would be sent."
            : "{$count} reminders sent.");

        return self::SUCCESS;
    }
}
