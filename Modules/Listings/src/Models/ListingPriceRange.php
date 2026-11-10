<?php

namespace Modules\Listings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Database\Factories\ListingPriceRangeFactory;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property string $name
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $price_cents
 * @property int|null $included_hours
 * @property int|null $included_people
 * @property int|null $extra_hour_cents
 * @property int|null $extra_person_cents
 * @property int|null $deposit_required_cents
 * @property string|null $conditions
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 */
#[Fillable([
    'name',
    'starts_at',
    'ends_at',
    'price_cents',
    'included_hours',
    'included_people',
    'extra_hour_cents',
    'extra_person_cents',
    'deposit_required_cents',
    'conditions',
    'active',
])]
class ListingPriceRange extends Model
{
    /** @use HasFactory<ListingPriceRangeFactory> */
    use HasFactory;

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
     * @param  Builder<ListingPriceRange>  $query
     * @return Builder<ListingPriceRange>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function isNotExpired(): bool
    {
        return $this->ends_at->toDateString() >= now()->toDateString();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'price_cents' => 'integer',
            'included_hours' => 'integer',
            'included_people' => 'integer',
            'extra_hour_cents' => 'integer',
            'extra_person_cents' => 'integer',
            'deposit_required_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): ListingPriceRangeFactory
    {
        return ListingPriceRangeFactory::new();
    }
}
