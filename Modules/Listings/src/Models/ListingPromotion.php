<?php

namespace Modules\Listings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Database\Factories\ListingPromotionFactory;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property string $title
 * @property string|null $description
 * @property int|null $promotional_price_cents
 * @property int|null $previous_price_cents
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property array<int, string>|null $applies_on
 * @property string|null $conditions
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 */
#[Fillable([
    'title',
    'description',
    'promotional_price_cents',
    'previous_price_cents',
    'starts_at',
    'ends_at',
    'applies_on',
    'conditions',
    'active',
])]
class ListingPromotion extends Model
{
    /** @use HasFactory<ListingPromotionFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const WEEKDAYS = [
        'mon' => 'Lunes',
        'tue' => 'Martes',
        'wed' => 'Miércoles',
        'thu' => 'Jueves',
        'fri' => 'Viernes',
        'sat' => 'Sábado',
        'sun' => 'Domingo',
    ];

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
     * @param  Builder<ListingPromotion>  $query
     * @return Builder<ListingPromotion>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<ListingPromotion>  $query
     * @return Builder<ListingPromotion>
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->active()
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today);
    }

    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();

        return $this->active
            && $this->starts_at->toDateString() <= $today
            && $this->ends_at->toDateString() >= $today;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function weekdayOptions(): array
    {
        return collect(self::WEEKDAYS)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function appliesOnLabels(): array
    {
        return collect($this->applies_on ?? [])
            ->filter(fn (string $day): bool => $day !== '')
            ->unique()
            ->map(fn (string $day): string => self::WEEKDAYS[$day] ?? $day)
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'promotional_price_cents' => 'integer',
            'previous_price_cents' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'applies_on' => 'array',
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): ListingPromotionFactory
    {
        return ListingPromotionFactory::new();
    }
}
