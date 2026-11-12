<?php

namespace Modules\Platform\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name'])]
class GeoCountry extends Model
{
    /**
     * @return HasMany<GeoState, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(GeoState::class);
    }
}
