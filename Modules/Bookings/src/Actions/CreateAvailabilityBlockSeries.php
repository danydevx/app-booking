<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\AvailabilityRecurrence;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Bookings\Support\RecurrenceDateRange;
use Modules\Listings\Models\Listing;

final class CreateAvailabilityBlockSeries
{
    public function __construct(
        private readonly RecurrenceDateRange $recurrenceDates,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{series: AvailabilityBlockSeries, created: int, skipped: array<int, string>}
     */
    public function handle(Listing $listing, User $actor, array $data): array
    {
        $recurrence = AvailabilityRecurrence::from((string) $data['recurrence']);
        $dates = $this->recurrenceDates->dates(
            (string) $data['starts_on'],
            (string) $data['ends_on'],
            $recurrence,
            (array) ($data['weekdays'] ?? []),
        );

        return DB::transaction(function () use ($listing, $actor, $data, $recurrence, $dates): array {
            $series = new AvailabilityBlockSeries([
                'reason' => $data['reason'] ?? null,
                'recurrence' => $recurrence,
                'weekdays' => $data['weekdays'] ?? [],
                'starts_on' => (string) $data['starts_on'],
                'ends_on' => (string) $data['ends_on'],
            ]);
            $series->team_id = $listing->team_id;
            $series->listing_id = $listing->id;
            $series->created_by = $actor->id;
            $series->save();

            $existing = ListingUnavailability::query()
                ->where('listing_id', $listing->id)
                ->whereIn('date', $dates)
                ->pluck('date', 'date')
                ->all();

            $created = 0;
            $skipped = [];

            foreach ($dates as $date) {
                if (isset($existing[$date])) {
                    $skipped[] = $date;

                    continue;
                }

                $unavailability = new ListingUnavailability([
                    'date' => $date,
                    'status' => AvailabilityStatus::Blocked,
                    'reason' => $series->reason ?: 'Bloqueo recurrente',
                    'availability_block_series_id' => $series->id,
                    'created_by' => $actor->id,
                ]);
                $unavailability->listing_id = $listing->id;
                $unavailability->save();
                $created++;
            }

            activity('availability')
                ->performedOn($listing)
                ->causedBy($actor)
                ->withProperties([
                    'series_id' => $series->id,
                    'recurrence' => $recurrence->value,
                    'created' => $created,
                    'skipped' => $skipped,
                ])
                ->log('availability.series.created');

            return [
                'series' => $series->refresh(),
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }
}
