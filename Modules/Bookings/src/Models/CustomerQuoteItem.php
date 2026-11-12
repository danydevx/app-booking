<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_quote_id
 * @property int $team_id
 * @property string $type
 * @property string $description
 * @property string|null $details
 * @property string $unit
 * @property int $quantity
 * @property int $unit_price_cents
 * @property int $amount_cents
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'description',
    'details',
    'unit',
    'quantity',
    'unit_price_cents',
    'amount_cents',
    'sort_order',
])]
class CustomerQuoteItem extends Model
{
    /**
     * @return BelongsTo<CustomerQuote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'customer_quote_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'amount_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
