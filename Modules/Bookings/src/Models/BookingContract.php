<?php

namespace Modules\Bookings\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Bookings\Database\Factories\BookingContractFactory;
use Modules\Bookings\Enums\ContractStatus;

/**
 * @property int $id
 * @property int $team_id
 * @property int $booking_inquiry_id
 * @property int|null $created_by
 * @property string $title
 * @property string $body
 * @property ContractStatus $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $accepted_at
 * @property string|null $accepted_by_name
 * @property string|null $accepted_ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read BookingInquiry $bookingInquiry
 * @property-read User|null $creator
 */
#[Fillable(['title', 'body'])]
class BookingContract extends Model
{
    /** @use HasFactory<BookingContractFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<BookingInquiry, $this>
     */
    public function bookingInquiry(): BelongsTo
    {
        return $this->belongsTo(BookingInquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canBeAccepted(): bool
    {
        return $this->status === ContractStatus::Sent && $this->accepted_at === null;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BookingContractFactory
    {
        return BookingContractFactory::new();
    }
}
