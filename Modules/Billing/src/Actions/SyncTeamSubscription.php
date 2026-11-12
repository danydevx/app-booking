<?php

namespace Modules\Billing\Actions;

use App\Models\Team;
use Carbon\Carbon;
use Laravel\Cashier\Subscription;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;

class SyncTeamSubscription
{
    /**
     * Pull the team's subscriptions from Stripe and mirror the newest paid one
     * locally. Used to recover from missed or delayed webhook deliveries.
     */
    public function handle(Team $team): ?Subscription
    {
        if (! $team->stripe_id || blank(config('cashier.secret'))) {
            return null;
        }

        $client = new StripeClient(config('cashier.secret'));

        $subscriptions = $client->subscriptions->all([
            'customer' => $team->stripe_id,
            'status' => 'all',
            'limit' => 10,
        ]);

        /** @var array<int, StripeSubscription> $items */
        $items = array_values($subscriptions->data);

        return $this->apply($team, $items);
    }

    /**
     * @param  array<int, StripeSubscription>  $subscriptions
     */
    public function apply(Team $team, array $subscriptions): ?Subscription
    {
        $known = Subscription::query()
            ->where('team_id', $team->id)
            ->pluck('stripe_id')
            ->all();

        $candidate = $this->candidate($subscriptions, $known);

        if (! $candidate) {
            return null;
        }

        $firstItem = $candidate->items->data[0] ?? null;

        if (! $firstItem) {
            return null;
        }

        $subscription = Subscription::query()
            ->where('team_id', $team->id)
            ->where('stripe_id', $candidate->id)
            ->first() ?? new Subscription([
                'team_id' => $team->id,
                'stripe_id' => $candidate->id,
            ]);

        $subscription->fill([
            'type' => $candidate->metadata->type ?? 'default',
            'stripe_status' => $candidate->status,
            'stripe_price' => count($candidate->items->data) === 1 ? $firstItem->price->id : null,
            'quantity' => count($candidate->items->data) === 1 ? ($firstItem->quantity ?? null) : null,
            'trial_ends_at' => isset($candidate->trial_end) && $candidate->trial_end
                ? Carbon::createFromTimestamp($candidate->trial_end)
                : null,
            'ends_at' => $this->endsAt($candidate, $firstItem),
        ]);

        $subscription->save();

        foreach ($candidate->items->data as $item) {
            $subscription->items()->updateOrCreate([
                'stripe_id' => $item->id,
            ], [
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? null,
            ]);
        }

        return $subscription;
    }

    /**
     * @param  array<int, StripeSubscription>  $subscriptions
     * @param  array<int, string>  $known
     */
    private function candidate(array $subscriptions, array $known = []): ?StripeSubscription
    {
        $subscriptions = array_values(array_filter(
            $subscriptions,
            fn (StripeSubscription $subscription): bool => filled($subscription->id),
        ));

        if ($subscriptions === []) {
            return null;
        }

        usort(
            $subscriptions,
            fn (StripeSubscription $a, StripeSubscription $b): int => ($b->created ?? 0) <=> ($a->created ?? 0),
        );

        $paidStatuses = ['active', 'trialing', 'past_due'];

        // Keep an already mirrored subscription stable instead of switching to
        // the newest one when several exist for the same customer.
        foreach ($subscriptions as $subscription) {
            if (in_array($subscription->stripe_id ?? $subscription->id, $known, true)
                && in_array($subscription->status, $paidStatuses, true)) {
                return $subscription;
            }
        }

        foreach ($subscriptions as $subscription) {
            if (in_array($subscription->status, $paidStatuses, true)) {
                return $subscription;
            }
        }

        return $subscriptions[0];
    }

    private function endsAt(StripeSubscription $subscription, mixed $firstItem): ?Carbon
    {
        if ($subscription->cancel_at_period_end ?? false) {
            $periodEnd = $firstItem->current_period_end
                ?? $subscription->current_period_end
                ?? null;

            return $periodEnd ? Carbon::createFromTimestamp($periodEnd) : null;
        }

        if (($subscription->cancel_at ?? null) || ($subscription->canceled_at ?? null)) {
            return Carbon::createFromTimestamp(
                $subscription->cancel_at ?? $subscription->canceled_at,
            );
        }

        return null;
    }
}
