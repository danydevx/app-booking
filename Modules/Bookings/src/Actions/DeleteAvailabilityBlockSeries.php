<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Bookings\Models\ListingUnavailability;

final class DeleteAvailabilityBlockSeries
{
    public function handle(AvailabilityBlockSeries $series, User $actor): int
    {
        return DB::transaction(function () use ($series, $actor): int {
            $today = CarbonImmutable::now()->toDateString();

            $deleted = ListingUnavailability::query()
                ->where('availability_block_series_id', $series->id)
                ->where('status', AvailabilityStatus::Blocked->value)
                ->where('date', '>=', $today)
                ->delete();

            $listing = $series->listing;

            activity('availability')
                ->performedOn($listing)
                ->causedBy($actor)
                ->withProperties([
                    'series_id' => $series->id,
                    'deleted_future_dates' => $deleted,
                ])
                ->log('availability.series.deleted');

            $series->delete();

            return $deleted;
        });
    }
}
