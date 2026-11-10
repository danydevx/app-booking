<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\CustomerFactory;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $whatsapp_phone
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, BookingInquiry> $inquiries
 */
#[Fillable(['name', 'email', 'phone', 'whatsapp_phone', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<BookingInquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(BookingInquiry::class)->latest();
    }

    /**
     * @return HasMany<CustomerActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class)->latest('occurred_at');
    }

    /**
     * @return HasMany<CustomerTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(CustomerTask::class);
    }

    /**
     * @return BelongsToMany<CustomerTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_assignments', 'customer_id', 'customer_tag_id')
            ->withTimestamps();
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
