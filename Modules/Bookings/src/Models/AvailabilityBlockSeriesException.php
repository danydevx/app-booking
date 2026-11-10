<?php

namespace Modules\Bookings\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $availability_block_series_id
 * @property CarbonImmutable $date
 * @property-read AvailabilityBlockSeries $series
 */
class AvailabilityBlockSeriesException extends Model
{
    protected $table = 'availability_block_series_exceptions';

    /**
     * @return array<int, string>
     */
    protected $fillable = ['availability_block_series_id', 'date'];

    /**
     * @return BelongsTo<AvailabilityBlockSeries, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(AvailabilityBlockSeries::class, 'availability_block_series_id');
    }

    /**
     * @return Attribute<CarbonImmutable, string>
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): CarbonImmutable => CarbonImmutable::parse($value)->startOfDay(),
            set: fn (string $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }
}
