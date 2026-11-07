<?php

namespace Modules\Platform\Support;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Modules\Listings\Models\Listing;
use Spatie\Activitylog\Models\Activity;

final class PlatformAccountPresenter
{
    /**
     * @param  array<int, int>  $listingCounts
     * @param  array<int, string|null>  $stripeStatuses
     * @return array<string, mixed>
     */
    public static function indexRow(
        Team $team,
        Entitlements $entitlements,
        array $listingCounts,
        array $stripeStatuses,
    ): array {
        $plan = $entitlements->planFor($team);
        $owner = self::owner($team);

        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'owner_name' => $owner?->name,
            'owner_email' => $owner?->email,
            'plan' => $plan->name,
            'plan_slug' => $plan->slug,
            'status' => $entitlements->statusFor($team),
            'stripe_status' => $stripeStatuses[$team->id] ?? null,
            'listings_count' => (int) ($listingCounts[$team->id] ?? 0),
            'members_count' => (int) $team->members_count,
            'is_suspended' => $team->isSuspended(),
            'suspended_at' => $team->suspended_at?->toDateString(),
            'created_at' => $team->created_at?->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @param  Collection<int, Activity>  $activities
     * @return array<string, mixed>
     */
    public static function detail(
        Team $team,
        Entitlements $entitlements,
        ?Subscription $subscription,
        Collection $listings,
        Collection $activities,
        bool $canSuspend,
    ): array {
        $plan = $entitlements->planFor($team);

        return [
            'account' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'timezone' => $team->timezone,
                'created_at' => $team->created_at?->toIso8601String(),
                'is_suspended' => $team->isSuspended(),
                'suspended_at' => $team->suspended_at?->toIso8601String(),
                'suspension_reason' => $team->suspension_reason,
                'stripe_id' => $team->stripe_id,
                'stripe_dashboard_url' => StripeDashboard::customerUrl($team->stripe_id),
            ],
            'owner' => self::owner($team),
            'members' => $team->members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role->value,
                'role_label' => $member->pivot->role->label(),
                'joined_at' => $member->pivot->created_at?->toDateString(),
            ])->all(),
            'listings' => $listings->map(fn (Listing $listing): array => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'status' => $listing->status->value,
                'status_label' => $listing->status->label(),
                'city' => $listing->city,
                'is_suspended' => $listing->isSuspendedByPlatform(),
                'public_url' => $listing->isPubliclyVisible()
                    ? route('public.listings.show', ['listing' => $listing->slug])
                    : null,
                'platform_url' => route('platform.listings.show', ['listing' => $listing->slug]),
            ])->all(),
            'billing' => [
                'plan' => $plan->name,
                'plan_slug' => $plan->slug,
                'status' => $entitlements->statusFor($team),
                'stripe_status' => $subscription?->stripe_status,
                'ends_at' => $subscription?->ends_at?->toDateString(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toDateString(),
                'has_customer' => $team->stripe_id !== null,
                'listings_limit' => $plan->limit('listings'),
                'dashboard_url' => StripeDashboard::customerUrl($team->stripe_id),
            ],
            'activities' => $activities
                ->map(fn (Activity $activity): array => ActivityPresenter::present($activity))
                ->all(),
            'can' => [
                'suspend' => $canSuspend,
            ],
            'urls' => [
                'index' => route('platform.accounts.index'),
                'suspend' => route('platform.accounts.suspend', ['account' => $team->slug]),
                'restore' => route('platform.accounts.restore', ['account' => $team->slug]),
            ],
        ];
    }

    public static function owner(Team $team): ?User
    {
        return $team->members->first(
            fn (User $member): bool => $member->pivot->role === TeamRole::Owner,
        );
    }
}
