<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Bookings\Database\Factories\CustomerQuoteFactory;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Listings\Models\Listing;

/**
 * @property int $id
 * @property int $team_id
 * @property int $customer_id
 * @property int $listing_id
 * @property int|null $created_by
 * @property int|null $converted_inquiry_id
 * @property string $public_token
 * @property QuoteStatus $status
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $event_type
 * @property int|null $guests
 * @property int $subtotal_cents
 * @property int $deposit_cents
 * @property string $currency
 * @property string|null $notes
 * @property string|null $terms
 * @property Carbon|null $expires_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Customer $customer
 * @property-read Listing $listing
 * @property-read User|null $creator
 * @property-read BookingInquiry|null $convertedInquiry
 */
#[Fillable([
    'start_date',
    'end_date',
    'event_type',
    'guests',
    'subtotal_cents',
    'deposit_cents',
    'currency',
    'notes',
    'terms',
    'expires_at',
])]
class CustomerQuote extends Model
{
    /** @use HasFactory<CustomerQuoteFactory> */
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
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<BookingInquiry, $this>
     */
    public function convertedInquiry(): BelongsTo
    {
        return $this->belongsTo(BookingInquiry::class, 'converted_inquiry_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function publicUrl(): string
    {
        return route('public.quotes.show', ['token' => $this->public_token]);
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerQuote $quote): void {
            $quote->public_token ??= Str::random(56);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'subtotal_cents' => 'integer',
            'deposit_cents' => 'integer',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    protected static function newFactory(): CustomerQuoteFactory
    {
        return CustomerQuoteFactory::new();
    }
}
