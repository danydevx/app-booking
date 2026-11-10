<?php

namespace Modules\Bookings\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\AvailabilityBlockSeriesException;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

final class UnblockListingDateRange
{
    public function handle(Listing $listing, string $startDate, string $endDate): int
    {
        return DB::transaction(function () use ($listing, $startDate, $endDate): int {
            $conflicts = ListingUnavailability::query()
                ->where('listing_id', $listing->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('status', [
                    AvailabilityStatus::Held->value,
                    AvailabilityStatus::Booked->value,
                ])
                ->orderBy('date')
                ->pluck('date')
                ->map(fn ($date): string => (string) $date)
                ->all();

            if ($conflicts !== []) {
                throw ValidationException::withMessages([
                    'start_date' => 'El rango incluye fechas reservadas o apartadas: '.implode(', ', $conflicts),
                ]);
            }

            $blocked = ListingUnavailability::query()
                ->where('listing_id', $listing->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', AvailabilityStatus::Blocked->value)
                ->get();

            if ($blocked->isEmpty()) {
                return 0;
            }

            foreach ($blocked as $date) {
                if ($date->availability_block_series_id !== null) {
                    AvailabilityBlockSeriesException::query()->firstOrCreate([
                        'availability_block_series_id' => $date->availability_block_series_id,
                        'date' => $date->date->toDateString(),
                    ]);
                }
            }

            return ListingUnavailability::query()
                ->whereKey($blocked->modelKeys())
                ->delete();
        });
    }
}
