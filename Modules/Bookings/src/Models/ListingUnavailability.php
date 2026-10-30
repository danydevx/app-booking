<?php

namespace Modules\Bookings\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Bookings\Database\Factories\ListingUnavailabilityFactory;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Listings\Models\Listing;

/**
 * @property int $id
 * @property int $listing_id
 * @property CarbonImmutable $date
 * @property AvailabilityStatus $status
 * @property string|null $reason
 * @property int|null $booking_inquiry_id
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Listing $listing
 * @property-read BookingInquiry|null $inquiry
 */
#[Fillable(['date', 'status', 'reason', 'booking_inquiry_id', 'created_by'])]
class ListingUnavailability extends Model
{
    /** @use HasFactory<ListingUnavailabilityFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'listing_unavailability';

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<BookingInquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(BookingInquiry::class, 'booking_inquiry_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return Attribute<CarbonImmutable, string>
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): CarbonImmutable => CarbonImmutable::parse($value)->startOfDay(),
            set: fn (string $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AvailabilityStatus::class,
        ];
    }

    protected static function newFactory(): ListingUnavailabilityFactory
    {
        return ListingUnavailabilityFactory::new();
    }
}
