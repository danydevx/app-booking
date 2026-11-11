<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $stripe_price_id
 * @property string $interval
 * @property bool $active
 * @property array<string, int>|null $limits
 * @property array<string, bool>|null $features
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'slug',
    'name',
    'description',
    'stripe_price_id',
    'interval',
    'active',
    'limits',
    'features',
])]
class BillingPlan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'limits' => 'array',
            'features' => 'array',
        ];
    }
}
