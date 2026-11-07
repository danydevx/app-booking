<?php

namespace Modules\Billing\Support;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Stripe\Exception\ApiErrorException;

final class BillingPageData
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly PlanCatalog $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Team $team, string $checkoutResult): array
    {
        $currentPlan = $this->entitlements->planFor($team);
        $subscription = $this->subscription($team);

        $usage = [
            'listings' => DB::table('listings')->where('team_id', $team->id)->count(),
            'customers' => DB::table('customers')->where('team_id', $team->id)->count(),
        ];

        $plans = collect($this->catalog->all())
            ->map(fn (Plan $plan): array => [
                ...$plan->toArray(),
                'is_current' => $plan->slug === $currentPlan->slug,
                'checkout_url' => $plan->isFree()
                    ? null
                    : route('billing.checkout', [
                        'current_team' => $team->slug,
                        'plan' => $plan->slug,
                    ]),
                'usage' => $plan->slug === $currentPlan->slug ? $usage : null,
            ])
            ->values()
            ->all();

        return [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'plan' => [
                ...$currentPlan->toArray(),
                'usage' => $usage,
            ],
            'plans' => $plans,
            'subscription' => [
                'status' => $this->entitlements->statusFor($team),
                'stripe_status' => $subscription?->stripe_status,
                'ends_at' => $subscription?->ends_at?->toDateString(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toDateString(),
                'has_customer' => $team->stripe_id !== null,
                'stripe_configured' => filled(config('cashier.secret')),
            ],
            'labels' => [
                'limits' => (array) config('billing.limit_labels', []),
                'features' => (array) config('billing.feature_labels', []),
            ],
            'invoices' => $this->invoices($team),
            'checkoutResult' => $checkoutResult,
            'urls' => [
                'index' => route('billing.index', ['current_team' => $team->slug]),
                'checkout' => route('billing.checkout', ['current_team' => $team->slug]),
                'portal' => route('billing.portal', ['current_team' => $team->slug]),
            ],
        ];
    }

    private function subscription(Team $team): ?Subscription
    {
        /** @var Subscription|null $subscription */
        $subscription = $team->subscriptions()
            ->where('type', 'default')
            ->first();

        return $subscription;
    }

    /**
     * @return array<int, array{id: string, date: string, total: string, status: string, url: string|null}>
     */
    private function invoices(Team $team): array
    {
        if (! $team->stripe_id || blank(config('cashier.secret'))) {
            return [];
        }

        try {
            $invoices = [];

            foreach ($team->invoices() as $invoice) {
                $stripeInvoice = $invoice->asStripeInvoice();

                $invoices[] = [
                    'id' => (string) $stripeInvoice->id,
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'status' => (string) $stripeInvoice->status,
                    'url' => $stripeInvoice->hosted_invoice_url,
                ];
            }

            return $invoices;
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }
}
