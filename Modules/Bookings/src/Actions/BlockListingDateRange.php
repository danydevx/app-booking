<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Support\AvailabilityService;
use Modules\Bookings\Support\DateRange;
use Modules\Listings\Models\Listing;

final class BlockListingDateRange
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BlockListingDate $blockDate,
    ) {}

    public function handle(
        Listing $listing,
        string $startDate,
        string $endDate,
        ?string $reason,
        User $user,
    ): int {
        try {
            return DB::transaction(function () use ($listing, $startDate, $endDate, $reason, $user): int {
                $conflicts = $this->availability->hardConflictingDates($listing, $startDate, $endDate);

                if ($conflicts !== []) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Algunas fechas ya están reservadas o apartadas: '.implode(', ', $conflicts),
                    ]);
                }

                $dates = DateRange::dates($startDate, $endDate);

                foreach ($dates as $date) {
                    $this->blockDate->handle($listing, $date, $reason, $user);
                }

                return count($dates);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000' || str_contains($exception->getMessage(), 'UNIQUE')) {
                throw ValidationException::withMessages([
                    'start_date' => 'Algunas fechas ya no están disponibles.',
                ]);
            }

            throw $exception;
        }
    }
}
