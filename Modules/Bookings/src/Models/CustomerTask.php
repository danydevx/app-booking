<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\CustomerTaskFactory;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Enums\CustomerTaskStatus;

/**
 * @property int $id
 * @property int $team_id
 * @property int $customer_id
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property string $title
 * @property string|null $notes
 * @property Carbon|null $due_at
 * @property CustomerTaskPriority $priority
 * @property CustomerTaskStatus $status
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Customer $customer
 * @property-read User|null $assignee
 * @property-read User|null $creator
 */
#[Fillable(['title', 'notes', 'due_at', 'priority', 'assigned_to'])]
class CustomerTask extends Model
{
    /** @use HasFactory<CustomerTaskFactory> */
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<CustomerTask>  $query
     * @return Builder<CustomerTask>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', CustomerTaskStatus::Open->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => CustomerTaskPriority::class,
            'status' => CustomerTaskStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CustomerTaskFactory
    {
        return CustomerTaskFactory::new();
    }
}
