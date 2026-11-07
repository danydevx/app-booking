<?php

namespace Modules\Bookings\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

final class AvailabilityService
{
    /**
     * @return Collection<int, ListingUnavailability>
     */
    public function unavailableBetween(
        Listing $listing,
        string $from,
        string $to,
    ): Collection {
        return ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }

    /**
     * @return array<int, array{date: string, status: string, label: string, reason: string|null}>
     */
    public function blockedDates(Listing $listing, string $from, string $to): array
    {
        return $this->unavailableBetween($listing, $from, $to)
            ->map(fn (ListingUnavailability $date): array => [
                'date' => $date->date->toDateString(),
                'status' => $date->status->value,
                'label' => $date->status->label(),
                'reason' => $date->reason,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function conflictingDates(Listing $listing, string $start, string $end): array
    {
        return $this->datesWithStatuses($listing, $start, $end, [
            AvailabilityStatus::Blocked,
            AvailabilityStatus::Held,
            AvailabilityStatus::Booked,
        ]);
    }

    /**
     * Dates that cannot be overwritten by a manual block.
     *
     * @return array<int, string>
     */
    public function hardConflictingDates(Listing $listing, string $start, string $end): array
    {
        return $this->datesWithStatuses($listing, $start, $end, [
            AvailabilityStatus::Held,
            AvailabilityStatus::Booked,
        ]);
    }

    /**
     * @param  array<int, AvailabilityStatus>  $statuses
     * @return array<int, string>
     */
    private function datesWithStatuses(
        Listing $listing,
        string $start,
        string $end,
        array $statuses,
    ): array {
        $dates = DateRange::dates($start, $end);

        if ($dates === []) {
            return [];
        }

        $unavailable = ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->whereIn('date', $dates)
            ->whereIn('status', array_map(fn (AvailabilityStatus $status): string => $status->value, $statuses))
            ->get(['date'])
            ->map(fn (ListingUnavailability $date): string => $date->date->toDateString())
            ->all();

        return array_values(array_intersect($dates, $unavailable));
    }

    public function isRangeAvailable(Listing $listing, string $start, string $end): bool
    {
        return $this->conflictingDates($listing, $start, $end) === [];
    }

    public function publicCalendarEnd(): string
    {
        return CarbonImmutable::now()
            ->addDays((int) config('bookings.public_calendar_days', 90))
            ->toDateString();
    }
}
