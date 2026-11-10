<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Bookings\Enums\AvailabilityRecurrence;
use Modules\Listings\Models\Listing;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property string|null $reason
 * @property AvailabilityRecurrence $recurrence
 * @property array<int, int>|null $weekdays
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 * @property-read User|null $creator
 * @property-read Collection<int, ListingUnavailability> $unavailabilities
 * @property-read Collection<int, AvailabilityBlockSeriesException> $exceptions
 */
#[Fillable(['reason', 'recurrence', 'weekdays', 'starts_on', 'ends_on'])]
class AvailabilityBlockSeries extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ListingUnavailability, $this>
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(ListingUnavailability::class, 'availability_block_series_id');
    }

    /**
     * @return HasMany<AvailabilityBlockSeriesException, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(AvailabilityBlockSeriesException::class, 'availability_block_series_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recurrence' => AvailabilityRecurrence::class,
            'weekdays' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
