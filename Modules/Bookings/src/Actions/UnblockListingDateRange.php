<?php

namespace Modules\Bookings\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\AvailabilityStatus;
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

            return ListingUnavailability::query()
                ->where('listing_id', $listing->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', AvailabilityStatus::Blocked->value)
                ->delete();
        });
    }
}
