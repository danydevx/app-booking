<?php

namespace Modules\Platform\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Listings\Models\Listing;
use Modules\Platform\Database\Factories\ListingClaimFactory;
use Modules\Platform\Enums\ListingClaimRole;
use Modules\Platform\Enums\ListingClaimStatus;

/**
 * @property int $id
 * @property int $listing_id
 * @property int|null $team_id
 * @property int|null $user_id
 * @property int|null $target_team_id
 * @property string $business_name
 * @property ListingClaimRole $role
 * @property string $claimant_name
 * @property string $claimant_email
 * @property string $claimant_phone
 * @property string|null $whatsapp_phone
 * @property string $message
 * @property string|null $evidence_path
 * @property string|null $evidence_name
 * @property string|null $evidence_mime
 * @property int|null $evidence_size
 * @property ListingClaimStatus $status
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by_id
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Listing $listing
 * @property-read Team|null $team
 * @property-read User|null $claimant
 * @property-read Team|null $targetTeam
 * @property-read User|null $reviewedBy
 */
#[Fillable([
    'listing_id',
    'team_id',
    'user_id',
    'target_team_id',
    'business_name',
    'role',
    'claimant_name',
    'claimant_email',
    'claimant_phone',
    'whatsapp_phone',
    'message',
    'evidence_path',
    'evidence_name',
    'evidence_mime',
    'evidence_size',
    'status',
    'rejection_reason',
    'reviewed_by_id',
    'reviewed_at',
])]
class ListingClaim extends Model
{
    /** @use HasFactory<ListingClaimFactory> */
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
    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function targetTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'target_team_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * @param  Builder<ListingClaim>  $query
     * @return Builder<ListingClaim>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ListingClaimStatus::Pending->value);
    }

    public function isPending(): bool
    {
        return $this->status === ListingClaimStatus::Pending;
    }

    public function hasEvidence(): bool
    {
        return filled($this->evidence_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ListingClaimRole::class,
            'status' => ListingClaimStatus::class,
            'evidence_size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ListingClaimFactory
    {
        return ListingClaimFactory::new();
    }
}
