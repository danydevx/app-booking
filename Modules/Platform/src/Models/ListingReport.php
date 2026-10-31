<?php

namespace Modules\Platform\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Models\Listing;
use Modules\Platform\Database\Factories\ListingReportFactory;
use Modules\Platform\Enums\ReportReason;
use Modules\Platform\Enums\ReportResolution;
use Modules\Platform\Enums\ReportStatus;

/**
 * @property int $id
 * @property int $listing_id
 * @property int $team_id
 * @property string|null $reporter_email
 * @property ReportReason $reason
 * @property string|null $message
 * @property ReportStatus $status
 * @property ReportResolution|null $resolution_action
 * @property string|null $resolution_note
 * @property int|null $handled_by_id
 * @property Carbon|null $handled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Listing $listing
 * @property-read Team $team
 * @property-read User|null $handledBy
 */
#[Fillable([
    'listing_id',
    'team_id',
    'reporter_email',
    'reason',
    'message',
    'status',
    'resolution_action',
    'resolution_note',
    'handled_by_id',
    'handled_at',
])]
class ListingReport extends Model
{
    /** @use HasFactory<ListingReportFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    protected static function newFactory(): ListingReportFactory
    {
        return ListingReportFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'resolution_action' => ReportResolution::class,
            'handled_at' => 'datetime',
        ];
    }
}
