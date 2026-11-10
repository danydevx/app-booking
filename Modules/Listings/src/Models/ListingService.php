<?php

namespace Modules\Listings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Database\Factories\ListingServiceFactory;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Support\TerraceServiceCatalog;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property string $service_key
 * @property string|null $description
 * @property bool $is_included
 * @property int|null $price_cents
 * @property ListingServicePriceUnit $price_unit
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 */
#[Fillable([
    'service_key',
    'description',
    'is_included',
    'price_cents',
    'price_unit',
    'active',
])]
class ListingService extends Model
{
    /** @use HasFactory<ListingServiceFactory> */
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
     * @param  Builder<ListingService>  $query
     * @return Builder<ListingService>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function serviceLabel(): string
    {
        return TerraceServiceCatalog::label($this->service_key);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'price_cents' => 'integer',
            'price_unit' => ListingServicePriceUnit::class,
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): ListingServiceFactory
    {
        return ListingServiceFactory::new();
    }
}
