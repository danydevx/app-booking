<?php

namespace Modules\Billing\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Http\Requests\CheckoutRequest;
use Modules\Billing\Support\BillingPageData;
use Modules\Billing\Support\Entitlements;
use Modules\Billing\Support\PlanCatalog;
use Stripe\Exception\ApiErrorException;

class BillingController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly PlanCatalog $catalog,
        private readonly BillingPageData $pageData,
    ) {}

    public function index(Request $request): Response
    {
        $team = $this->currentTeam($request);

        Gate::authorize('manageBilling', $team);

        return Inertia::render('Billing/Index', $this->pageData->for(
            $team,
            $request->string('checkout')->toString(),
        ));
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $team = $this->currentTeam($request);

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
        $team = $this->currentTeam($request);

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
}
