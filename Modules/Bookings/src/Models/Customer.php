<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\CustomerFactory;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Listings\Models\Listing;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $listing_id
 * @property int|null $assigned_to
 * @property Carbon|null $email_opt_out_at
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $whatsapp_phone
 * @property string|null $notes
 * @property CustomerPipelineStage $pipeline_stage
 * @property int|null $merged_into_id
 * @property Carbon|null $merged_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $anonymized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Listing|null $listing
 * @property-read User|null $assignee
 * @property-read Collection<int, BookingInquiry> $inquiries
 */
#[Fillable(['name', 'email', 'phone', 'whatsapp_phone', 'notes', 'listing_id', 'assigned_to'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pipeline_stage' => 'lead',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pipeline_stage' => CustomerPipelineStage::class,
            'merged_at' => 'datetime',
            'archived_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'email_opt_out_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
