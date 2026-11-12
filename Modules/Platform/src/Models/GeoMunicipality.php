<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $geo_state_id
 * @property string $code
 * @property string $full_code
 * @property string $name
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GeoMunicipalityCoverage|null $coverage
 */
#[Fillable(['geo_state_id', 'code', 'full_code', 'name', 'sort_order'])]
class GeoMunicipality extends Model
{
    /**
     * @return BelongsTo<GeoState, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(GeoState::class, 'geo_state_id');
    }

    /**
     * @return HasOne<GeoMunicipalityCoverage, $this>
     */
    public function coverage(): HasOne
    {
        return $this->hasOne(GeoMunicipalityCoverage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
