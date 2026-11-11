<?php

namespace Modules\Listings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Database\Factories\ListingPackageFactory;
use Modules\Listings\Support\CommercialServiceCatalog;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property string $name
 * @property string|null $description
 * @property int $price_cents
 * @property int $duration_hours
 * @property int $included_people
 * @property array<int, string>|null $included_services
 * @property string|null $conditions
 * @property bool $active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 */
#[Fillable([
    'name',
    'description',
    'price_cents',
    'duration_hours',
    'included_people',
    'included_services',
    'conditions',
    'active',
    'sort_order',
])]
class ListingPackage extends Model
{
    /** @use HasFactory<ListingPackageFactory> */
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
     * @param  Builder<ListingPackage>  $query
     * @return Builder<ListingPackage>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function serviceItems(): array
    {
        return $this->items($this->included_services);
    }

    /**
     * @param  array<int, string>|null  $keys
     * @return array<int, array{key: string, label: string}>
     */
    private function items(?array $keys): array
    {
        return collect($keys ?? [])
            ->filter(fn (string $key): bool => $key !== '')
            ->unique()
            ->map(fn (string $key): array => [
                'key' => $key,
                'label' => CommercialServiceCatalog::label($key),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'duration_hours' => 'integer',
            'included_people' => 'integer',
            'included_services' => 'array',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): ListingPackageFactory
    {
        return ListingPackageFactory::new();
    }
}
