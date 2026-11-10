<?php

namespace Modules\Bookings\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Bookings\Enums\AvailabilityRecurrence;

final class RecurrenceDateRange
{
    /**
     * @param  array<int, int|string>  $weekdays
     * @return array<int, string>
     */
    public function dates(
        string $start,
        string $end,
        AvailabilityRecurrence $recurrence,
        array $weekdays = [],
    ): array {
        $startDate = CarbonImmutable::parse($start)->startOfDay();
        $endDate = CarbonImmutable::parse($end)->startOfDay();

        if ($endDate->lt($startDate)) {
            return [];
        }

        $dates = $recurrence === AvailabilityRecurrence::Monthly
            ? $this->monthlyDates($startDate, $endDate)
            : $this->weeklyDates($startDate, $endDate, $recurrence, $weekdays);

        sort($dates);

        return array_values(array_unique($dates));
    }

    /**
     * @return array<int, string>
     */
    private function monthlyDates(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];
        $cursor = $start->startOfMonth();

        while ($cursor->lte($end)) {
            $day = min($start->day, $cursor->daysInMonth);
            $date = $cursor->day($day);

            if ($date->betweenIncluded($start, $end)) {
                $dates[] = $date->toDateString();
            }

            $cursor = $cursor->addMonthNoOverflow();
        }

        return $dates;
    }

    /**
     * @param  array<int, int|string>  $weekdays
     * @return array<int, string>
     */
    private function weeklyDates(
        CarbonImmutable $start,
        CarbonImmutable $end,
        AvailabilityRecurrence $recurrence,
        array $weekdays,
    ): array {
        $selected = array_map('intval', $weekdays);
        $startWeek = $start->startOfWeek(CarbonInterface::MONDAY);
        $dates = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            if (! in_array($date->dayOfWeekIso, $selected, true)) {
                continue;
            }

            if ($recurrence === AvailabilityRecurrence::Biweekly) {
                $weeks = intdiv(
                    (int) $startWeek->diffInDays($date->startOfWeek(CarbonInterface::MONDAY)),
                    7,
                );

                if ($weeks % 2 !== 0) {
                    continue;
                }
            }

            $dates[] = $date->toDateString();
        }

        return $dates;
    }
}
