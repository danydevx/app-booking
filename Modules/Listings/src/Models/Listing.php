<?php

namespace Modules\Listings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Modules\Listings\Database\Factories\ListingFactory;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Support\TerraceServiceCatalog;

/**
 * @property int $id
 * @property int $team_id
 * @property string $type
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $postal_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $capacity
 * @property int|null $price_from_cents
 * @property int|null $price_monday_cents
 * @property int|null $price_tuesday_cents
 * @property int|null $price_wednesday_cents
 * @property int|null $price_thursday_cents
 * @property int|null $price_friday_cents
 * @property int|null $price_saturday_cents
 * @property int|null $price_sunday_cents
 * @property array<string, array{hours: int|null, entry_time: string|null, exit_time: string|null, price_cents: int|null}>|null $daily_pricing
 * @property int|null $price_weekday_cents
 * @property int|null $price_weekend_cents
 * @property int|null $included_hours
 * @property int|null $included_people
 * @property int|null $extra_hour_cents
 * @property int|null $extra_person_cents
 * @property int|null $deposit_required_cents
 * @property string|null $price_notes
 * @property string $currency
 * @property array<int, string>|null $services
 * @property array<string, mixed>|null $details
 * @property string|null $rules
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $whatsapp_phone
 * @property string $timezone
 * @property ListingStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $suspended_at
 * @property ListingStatus|null $suspended_from_status
 * @property string|null $suspension_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ListingMedia> $media
 * @property-read Collection<int, ListingPackage> $packages
 * @property-read Collection<int, ListingService> $commercialServices
 * @property-read Collection<int, ListingPromotion> $promotions
 * @property-read Collection<int, ListingPriceRange> $priceRanges
 * @property-read int $images_count
 * @property-read int $videos_count
 */
#[Fillable([
    'name',
    'description',
    'address',
    'city',
    'state',
    'country',
    'postal_code',
    'latitude',
    'longitude',
    'capacity',
    'price_from_cents',
    'price_monday_cents',
    'price_tuesday_cents',
    'price_wednesday_cents',
    'price_thursday_cents',
    'price_friday_cents',
    'price_saturday_cents',
    'price_sunday_cents',
    'daily_pricing',
    'price_weekday_cents',
    'price_weekend_cents',
    'included_hours',
    'included_people',
    'extra_hour_cents',
    'extra_person_cents',
    'deposit_required_cents',
    'price_notes',
    'currency',
    'services',
    'details',
    'rules',
    'contact_name',
    'contact_email',
    'contact_phone',
    'whatsapp_phone',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<ListingMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ListingPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ListingPackage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ListingService, $this>
     */
    public function commercialServices(): HasMany
    {
        return $this->hasMany(ListingService::class)->orderBy('id');
    }

    /**
     * @return HasMany<ListingPromotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(ListingPromotion::class)->orderByDesc('starts_at')->orderByDesc('id');
    }

    /**
     * @return HasMany<ListingPriceRange, $this>
     */
    public function priceRanges(): HasMany
    {
        return $this->hasMany(ListingPriceRange::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * @return HasMany<ListingMedia, $this>
     */
    public function images(): HasMany
    {
        return $this->media()->where('type', ListingMediaType::Image->value);
    }

    /**
     * @return HasMany<ListingMedia, $this>
     */
    public function videos(): HasMany
    {
        return $this->media()->where('type', ListingMediaType::Video->value);
    }

    /**
     * @return HasOne<ListingMedia, $this>
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(ListingMedia::class)
            ->where('type', ListingMediaType::Image->value)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @param  Builder<Listing>  $query
     * @return Builder<Listing>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Published->value)
            ->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published && $this->published_at !== null;
    }

    /**
     * Determine if the listing has been suspended by platform moderation.
     */
    public function isSuspendedByPlatform(): bool
    {
        return $this->status === ListingStatus::Suspended && $this->suspended_at !== null;
    }

    /**
     * Determine if the listing can be exposed in public pages and search.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->isPublished()
            && $this->suspended_at === null
            && ($this->team?->isPubliclyVisible() ?? false);
    }

    /**
     * Restrict the query to listings that are publicly visible.
     *
     * @param  Builder<Listing>  $query
     * @return Builder<Listing>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()
            ->whereNull('suspended_at')
            ->whereHas('team', fn ($team) => $team
                ->whereNull('suspended_at')
                ->whereNull('deleted_at'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function distanceFrom(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371.0;
        $latFrom = deg2rad($latitude);
        $lngFrom = deg2rad($longitude);
        $latTo = deg2rad((float) $this->latitude);
        $lngTo = deg2rad((float) $this->longitude);
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected static function newFactory(): ListingFactory
    {
        return ListingFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'capacity' => 'integer',
            'price_from_cents' => 'integer',
            'price_monday_cents' => 'integer',
            'price_tuesday_cents' => 'integer',
            'price_wednesday_cents' => 'integer',
            'price_thursday_cents' => 'integer',
            'price_friday_cents' => 'integer',
            'price_saturday_cents' => 'integer',
            'price_sunday_cents' => 'integer',
            'daily_pricing' => 'array',
            'price_weekday_cents' => 'integer',
            'price_weekend_cents' => 'integer',
            'included_hours' => 'integer',
            'included_people' => 'integer',
            'extra_hour_cents' => 'integer',
            'extra_person_cents' => 'integer',
            'deposit_required_cents' => 'integer',
            'services' => 'array',
            'details' => 'array',
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
            'suspended_at' => 'datetime',
            'suspended_from_status' => ListingStatus::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function serviceLabels(): array
    {
        $available = TerraceServiceCatalog::all();

        return collect($this->services ?? [])
            ->map(fn (string $service): ?string => $available[$service] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
