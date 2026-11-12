<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $geo_municipality_id
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['geo_municipality_id', 'enabled'])]
class GeoMunicipalityCoverage extends Model
{
    /**
     * @return BelongsTo<GeoMunicipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(GeoMunicipality::class, 'geo_municipality_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
