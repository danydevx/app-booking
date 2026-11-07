<?php

namespace Modules\Bookings\Console\Commands;

use Illuminate\Console\Command;
use Modules\Bookings\Actions\ExpireStaleInquiries;

class ExpireBookingInquiries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-inquiries
        {--dry-run : Report stale inquiries without updating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire new/contacted inquiries whose requested dates have passed';

    public function handle(ExpireStaleInquiries $expireStaleInquiries): int
    {
        $count = $expireStaleInquiries->handle((bool) $this->option('dry-run'));

        $this->info($this->option('dry-run')
            ? "{$count} inquiries would be expired."
            : "{$count} inquiries expired.");

        return self::SUCCESS;
    }
}
