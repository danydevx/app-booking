<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $geo_state_id
 * @property bool $enabled
 * @property bool $all_municipalities
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['geo_state_id', 'enabled', 'all_municipalities'])]
class GeoStateCoverage extends Model
{
    /**
     * @return BelongsTo<GeoState, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(GeoState::class, 'geo_state_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'all_municipalities' => 'boolean',
        ];
    }
}
