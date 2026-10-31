<?php

namespace Modules\Billing\Support;

use App\Models\Team;
use Laravel\Cashier\Subscription;

final class Entitlements
{
    public function __construct(
        private readonly PlanCatalog $catalog,
    ) {}

    public function planFor(Team $team): Plan
    {
        $subscription = $this->subscription($team);

        if (! $subscription) {
            return $this->catalog->default();
        }

        $plan = $this->catalog->findByPriceId($subscription->stripe_price);

        if (! $plan || $plan->isFree() || ! $this->hasPaidAccess($subscription)) {
            return $this->catalog->default();
        }

        return $plan;
    }

    public function allows(Team $team, string $feature): bool
    {
        return $this->planFor($team)->allows($feature);
    }

    public function limit(Team $team, string $key): int
    {
        return $this->planFor($team)->limit($key);
    }

    public function canCreateListing(Team $team, int $currentCount): bool
    {
        return $currentCount < $this->limit($team, 'listings');
    }

    public function canUpload(Team $team, string $mediaType, int $currentCount): bool
    {
        $key = $mediaType === 'image'
            ? 'photos_per_listing'
            : 'videos_per_listing';

        return $currentCount < $this->limit($team, $key);
    }

    public function statusFor(Team $team): string
    {
        $subscription = $this->subscription($team);

        if (! $subscription) {
            return 'free';
        }

        if ($this->planFor($team)->slug !== $this->catalog->default()->slug) {
            if ($subscription->pastDue()) {
                return 'past_due_grace';
            }

            if ($subscription->onTrial()) {
                return 'trialing';
            }

            if ($subscription->onGracePeriod()) {
                return 'canceling';
            }

            return 'active';
        }

        if ($subscription->pastDue()) {
            return 'past_due_locked';
        }

        if ($subscription->canceled()) {
            return 'canceled';
        }

        return 'free';
    }

    private function subscription(Team $team): ?Subscription
    {
        if ($team->relationLoaded('subscriptions')) {
            /** @var Subscription|null $subscription */
            $subscription = $team->subscriptions->firstWhere('type', 'default');

            return $subscription;
        }

        /** @var Subscription|null $subscription */
        $subscription = $team->subscriptions()
            ->where('type', 'default')
            ->first();

        return $subscription;
    }

    private function hasPaidAccess(Subscription $subscription): bool
    {
        if ($subscription->active() || $subscription->onTrial() || $subscription->onGracePeriod()) {
            return true;
        }

        if ($subscription->pastDue()) {
            $updatedAt = $subscription->updated_at ?? $subscription->created_at;
            $graceDays = (int) config('billing.past_due_grace_days', 3);

            return $updatedAt !== null
                && $updatedAt->copy()->addDays($graceDays)->isFuture();
        }

        return false;
    }
}
