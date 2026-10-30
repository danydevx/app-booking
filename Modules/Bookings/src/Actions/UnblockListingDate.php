<?php

namespace Modules\Bookings\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

final class UnblockListingDate
{
    public function handle(Listing $listing, string $date): void
    {
        $unavailability = ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->whereDate('date', $date)
            ->first();

        if (! $unavailability) {
            return;
        }

        if ($unavailability->status !== AvailabilityStatus::Blocked) {
            throw ValidationException::withMessages([
                'date' => 'Solo puedes desbloquear fechas bloqueadas manualmente.',
            ]);
        }

        $unavailability->delete();
    }
}
