<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Bookings\Database\Factories\BookingInquiryFactory;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Listings\Models\Listing;

/**
 * @property int $id
 * @property int $team_id
 * @property int $listing_id
 * @property int|null $customer_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $whatsapp_phone
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property string|null $event_type
 * @property int|null $guests
 * @property string|null $message
 * @property InquiryStatus $status
 * @property string $source
 * @property CarbonImmutable|null $consent_at
 * @property string|null $ip_hash
 * @property CarbonImmutable|null $contacted_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $reminder_sent_at
 * @property CarbonImmutable|null $declined_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $expired_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $team
 * @property-read Listing $listing
 * @property-read Customer|null $customer
 * @property-read Collection<int, BookingDeposit> $deposits
 */
#[Fillable([
    'listing_id',
    'name',
    'email',
    'phone',
    'whatsapp_phone',
    'start_date',
    'end_date',
    'event_type',
    'guests',
    'message',
])]
class BookingInquiry extends Model
{
    /** @use HasFactory<BookingInquiryFactory> */
    use HasFactory;

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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<BookingDeposit, $this>
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(BookingDeposit::class)->latest();
    }

    /**
     * @return HasMany<ListingUnavailability, $this>
     */
    public function unavailableDates(): HasMany
    {
        return $this->hasMany(ListingUnavailability::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'guests' => 'integer',
            'status' => InquiryStatus::class,
            'consent_at' => 'datetime',
            'contacted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BookingInquiryFactory
    {
        return BookingInquiryFactory::new();
    }
}
