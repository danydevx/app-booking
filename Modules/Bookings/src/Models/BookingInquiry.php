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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
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
 * @property string|null $source_detail
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property string|null $referrer
 * @property CarbonImmutable|null $consent_at
 * @property string|null $ip_hash
 * @property string|null $portal_token
 * @property CarbonImmutable|null $customer_confirmed_at
 * @property int|null $feedback_rating
 * @property string|null $feedback_comment
 * @property CarbonImmutable|null $feedback_at
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
 * @property-read BookingContract|null $contract
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
    'source_detail',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
    'referrer',
    'portal_token',
    'customer_confirmed_at',
    'feedback_rating',
    'feedback_comment',
    'feedback_at',
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
     * @return HasOne<BookingContract, $this>
     */
    public function contract(): HasOne
    {
        return $this->hasOne(BookingContract::class);
    }

    /**
     * @return HasMany<ListingUnavailability, $this>
     */
    public function unavailableDates(): HasMany
    {
        return $this->hasMany(ListingUnavailability::class);
    }

    public function portalUrl(): string
    {
        if (blank($this->portal_token)) {
            $this->forceFill(['portal_token' => Str::random(56)])->save();
        }

        return route('public.bookings.portal', ['token' => $this->portal_token]);
    }

    public function canBeConfirmedByCustomer(): bool
    {
        return in_array($this->status, [
            InquiryStatus::New,
            InquiryStatus::Contacted,
            InquiryStatus::Accepted,
        ], true);
    }

    public function canReceiveFeedback(): bool
    {
        return $this->status === InquiryStatus::Accepted
            && $this->end_date->toDateString() < now($this->listing->timezone)->toDateString()
            && $this->feedback_at === null;
    }

    protected static function booted(): void
    {
        static::creating(function (BookingInquiry $inquiry): void {
            $inquiry->portal_token ??= Str::random(56);
        });
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
            'customer_confirmed_at' => 'datetime',
            'feedback_at' => 'datetime',
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
