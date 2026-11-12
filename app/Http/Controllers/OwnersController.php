<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Plan;
use Modules\Billing\Support\PlanCatalog;
use Stripe\StripeClient;
use Throwable;

class OwnersController extends Controller
{
    public function __invoke(Request $request, PlanCatalog $catalog): Response
    {
        $free = $catalog->get('free') ?? $catalog->default();
        $pro = $catalog->get('pro');

        $plans = [$this->presentPlan($free, 0)];

        if ($pro !== null) {
            $plans[] = $this->presentPlan($pro, $this->proPriceCents($pro));
        }

        $user = $request->user();
        $team = null;

        if ($user !== null) {
            $team = $user->currentTeam ?? $user->personalTeam();
        }

        return Inertia::render('Owners', [
            'plans' => $plans,
            'urls' => [
                'register' => route('register'),
                'login' => route('login'),
                'dashboard' => $team !== null
                    ? route('dashboard', ['current_team' => $team->slug])
                    : null,
                'billing' => $team !== null
                    ? route('billing.index', ['current_team' => $team->slug])
                    : null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPlan(Plan $plan, int $priceCents): array
    {
        return [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_cents' => $priceCents,
            'price_formatted' => $this->formatPrice($priceCents),
            'period' => $plan->interval === 'month' ? 'mes' : $plan->interval,
            'interval' => $plan->interval,
            'limits' => [
                'listings' => $plan->limit('listings'),
                'photos_per_listing' => $plan->limit('photos_per_listing'),
                'videos_per_listing' => $plan->limit('videos_per_listing'),
            ],
            'features' => [
                'customers' => $plan->allows('customers'),
            ],
            'is_free' => $plan->isFree(),
            'active' => $plan->active,
        ];
    }

    /**
     * Read the active Stripe price once per hour; fall back to the local plan
     * amount when Stripe is not configured (tests, offline) or the call fails.
     */
    private function proPriceCents(Plan $plan): int
    {
        $fallback = (int) config('billing.plans.pro.price_cents', 0);

        if ($plan->stripePriceId === null || blank(config('cashier.secret'))) {
            return $fallback;
        }

        return Cache::remember(
            'billing:owners:pro-price:'.$plan->stripePriceId,
            now()->addHour(),
            function () use ($plan, $fallback): int {
                try {
                    $price = (new StripeClient(config('cashier.secret')))
                        ->prices
                        ->retrieve($plan->stripePriceId);

                    if ($price->unit_amount !== null) {
                        return (int) $price->unit_amount;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }

                return $fallback;
            },
        );
    }

    private function formatPrice(int $cents): string
    {
        $currency = strtoupper((string) config('billing.currency', 'mxn'));
        $decimals = $cents % 100 === 0 ? 0 : 2;

        return '$'.number_format($cents / 100, $decimals, '.', ',').' '.$currency;
    }
}
