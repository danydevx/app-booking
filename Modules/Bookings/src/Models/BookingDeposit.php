<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\BookingDepositFactory;
use Modules\Bookings\Enums\DepositMethod;
use Modules\Bookings\Enums\DepositStatus;

/**
 * @property int $id
 * @property int $booking_inquiry_id
 * @property int $amount_cents
 * @property string $currency
 * @property DepositStatus $status
 * @property DepositMethod $method
 * @property string|null $reference
 * @property Carbon|null $received_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BookingInquiry $inquiry
 */
#[Fillable([
    'amount_cents',
    'currency',
    'status',
    'method',
    'reference',
    'received_at',
    'notes',
])]
class BookingDeposit extends Model
{
    /** @use HasFactory<BookingDepositFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BookingInquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(BookingInquiry::class, 'booking_inquiry_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => DepositStatus::class,
            'method' => DepositMethod::class,
            'received_at' => 'datetime',
        ];
    }

    public function amount(): float
    {
        return $this->amount_cents / 100;
    }

    protected static function newFactory(): BookingDepositFactory
    {
        return BookingDepositFactory::new();
    }
}
