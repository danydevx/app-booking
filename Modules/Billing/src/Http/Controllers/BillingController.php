<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Modules\Billing\Http\Requests\CheckoutRequest;
use Modules\Billing\Support\Entitlements;
use Modules\Billing\Support\Plan;
use Modules\Billing\Support\PlanCatalog;
use Stripe\Exception\ApiErrorException;

class BillingController extends Controller
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly PlanCatalog $catalog,
    ) {}

    public function index(Request $request): Response
    {
        $team = $this->team($request);

        Gate::authorize('manageBilling', $team);

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

        return Inertia::render('Billing/Index', [
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
            'checkoutResult' => $request->string('checkout')->toString(),
            'urls' => [
                'index' => route('billing.index', ['current_team' => $team->slug]),
                'checkout' => route('billing.checkout', ['current_team' => $team->slug]),
                'portal' => route('billing.portal', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $team = $this->team($request);

        Gate::authorize('manageBilling', $team);

        $plan = $this->catalog->get((string) $request->validated('plan'));

        if (! $plan || $plan->isFree() || ! $plan->active) {
            throw ValidationException::withMessages([
                'plan' => 'El plan seleccionado no está disponible para pago.',
            ]);
        }

        if ($this->entitlements->planFor($team)->slug === $plan->slug) {
            throw ValidationException::withMessages([
                'plan' => 'La cuenta ya tiene este plan.',
            ]);
        }

        if (! $plan->stripePriceId) {
            throw ValidationException::withMessages([
                'plan' => 'El precio de Stripe para este plan no está configurado.',
            ]);
        }

        if (blank(config('cashier.secret'))) {
            throw ValidationException::withMessages([
                'plan' => 'Stripe no está configurado en este entorno.',
            ]);
        }

        try {
            $checkout = $team->newSubscription('default', $plan->stripePriceId)
                ->withMetadata([
                    'team_id' => (string) $team->id,
                    'plan' => $plan->slug,
                ])
                ->checkout([
                    'success_url' => route('billing.index', [
                        'current_team' => $team->slug,
                    ]).'?checkout=success',
                    'cancel_url' => route('billing.index', [
                        'current_team' => $team->slug,
                    ]).'?checkout=cancelled',
                    'client_reference_id' => (string) $team->id,
                ]);
        } catch (ApiErrorException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'plan' => 'No se pudo iniciar el pago con Stripe. Intenta de nuevo.',
            ]);
        }

        return $checkout->redirect();
    }

    public function portal(Request $request): RedirectResponse
    {
        $team = $this->team($request);

        Gate::authorize('manageBilling', $team);

        if (! $team->stripe_id) {
            throw ValidationException::withMessages([
                'billing' => 'La cuenta todavía no tiene un cliente de Stripe.',
            ]);
        }

        if (blank(config('cashier.secret'))) {
            throw ValidationException::withMessages([
                'billing' => 'Stripe no está configurado en este entorno.',
            ]);
        }

        try {
            return $team->redirectToBillingPortal(route('billing.index', [
                'current_team' => $team->slug,
            ]));
        } catch (ApiErrorException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'billing' => 'No se pudo abrir el portal de facturación.',
            ]);
        }
    }

    private function team(Request $request): Team
    {
        return Team::query()
            ->whereKey($request->user()?->current_team_id)
            ->firstOrFail();
    }

    private function subscription(Team $team): ?Subscription
    {
        /** @var Subscription|null $subscription */
        $subscription = $team->subscriptions()
            ->where('type', 'default')
            ->first();

        return $subscription;
    }
}
