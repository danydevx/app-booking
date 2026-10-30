<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

final class BlockListingDate
{
    public function handle(
        Listing $listing,
        string $date,
        ?string $reason,
        User $user,
    ): ListingUnavailability {
        $today = CarbonImmutable::now($listing->timezone)->toDateString();

        if ($date < $today) {
            throw ValidationException::withMessages([
                'date' => 'No puedes bloquear una fecha pasada.',
            ]);
        }

        $existing = ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            if (in_array($existing->status, [AvailabilityStatus::Booked, AvailabilityStatus::Held], true)) {
                throw ValidationException::withMessages([
                    'date' => 'Esa fecha ya está reservada o apartada.',
                ]);
            }

            $existing->update([
                'reason' => $reason,
                'created_by' => $user->id,
            ]);

            return $existing->refresh();
        }

        $unavailability = new ListingUnavailability([
            'date' => $date,
            'status' => AvailabilityStatus::Blocked,
            'reason' => $reason,
            'created_by' => $user->id,
        ]);
        $unavailability->listing_id = $listing->id;
        $unavailability->save();

        return $unavailability;
    }
}
