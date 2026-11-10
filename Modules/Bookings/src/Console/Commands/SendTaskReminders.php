<?php

namespace Modules\Bookings\Console\Commands;

use Illuminate\Console\Command;
use Modules\Bookings\Actions\SendCustomerTaskReminders;

class SendTaskReminders extends Command
{
    protected $signature = 'bookings:send-task-reminders
        {--dry-run : Report reminders without sending them}';

    protected $description = 'Send daily digests for customer tasks due today or overdue';

    public function handle(SendCustomerTaskReminders $sendReminders): int
    {
        $count = $sendReminders->handle((bool) $this->option('dry-run'));

        $this->info($this->option('dry-run')
            ? "{$count} task reminders would be sent."
            : "{$count} task reminders sent.");

        return self::SUCCESS;
    }
}
