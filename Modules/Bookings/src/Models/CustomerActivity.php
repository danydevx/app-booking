<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\CustomerActivityFactory;
use Modules\Bookings\Enums\CustomerActivityType;

/**
 * @property int $id
 * @property int $team_id
 * @property int $customer_id
 * @property int|null $user_id
 * @property CustomerActivityType $type
 * @property string $body
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Customer $customer
 * @property-read User|null $user
 */
#[Fillable(['type', 'body', 'occurred_at', 'metadata'])]
class CustomerActivity extends Model
{
    /** @use HasFactory<CustomerActivityFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): CustomerActivityFactory
    {
        return CustomerActivityFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CustomerActivityType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
