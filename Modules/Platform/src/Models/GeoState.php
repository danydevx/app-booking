<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $geo_country_id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GeoStateCoverage|null $coverage
 */
#[Fillable(['geo_country_id', 'code', 'name', 'sort_order'])]
class GeoState extends Model
{
    /**
     * @return BelongsTo<GeoCountry, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeoCountry::class, 'geo_country_id');
    }

    /**
     * @return HasMany<GeoMunicipality, $this>
     */
    public function municipalities(): HasMany
    {
        return $this->hasMany(GeoMunicipality::class)->orderBy('name');
    }

    /**
     * @return HasOne<GeoStateCoverage, $this>
     */
    public function coverage(): HasOne
    {
        return $this->hasOne(GeoStateCoverage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
