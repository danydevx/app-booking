<?php

namespace Modules\Bookings\Support;

use Carbon\CarbonImmutable;

final class DateRange
{
    /**
     * @return array<int, string>
     */
    public static function dates(string $startDate, string $endDate): array
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        $dates = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    public static function days(string $startDate, string $endDate): int
    {
        return count(self::dates($startDate, $endDate));
    }
}
