<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\CustomerTagFactory;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, Customer> $customers
 */
#[Fillable(['name', 'slug', 'color'])]
class CustomerTag extends Model
{
    /** @use HasFactory<CustomerTagFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsToMany<Customer, $this>
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag_assignments', 'customer_tag_id', 'customer_id')
            ->withTimestamps();
    }

    protected static function newFactory(): CustomerTagFactory
    {
        return CustomerTagFactory::new();
    }
}
