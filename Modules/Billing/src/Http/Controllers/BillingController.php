<?php

namespace Modules\Billing\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Actions\CreateCheckoutSession;
use Modules\Billing\Actions\SyncTeamSubscription;
use Modules\Billing\Http\Requests\CheckoutRequest;
use Modules\Billing\Support\BillingPageData;
use Modules\Billing\Support\Entitlements;
use Modules\Billing\Support\PlanCatalog;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class BillingController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly PlanCatalog $catalog,
        private readonly BillingPageData $pageData,
        private readonly CreateCheckoutSession $createCheckoutSession,
        private readonly SyncTeamSubscription $syncTeamSubscription,
    ) {}

    public function index(Request $request): Response
    {
        $team = $this->currentTeam($request);

        Gate::authorize('manageBilling', $team);

        $checkoutResult = $request->string('checkout')->toString();

        if ($checkoutResult === 'success') {
            $this->syncMissingSubscription($team);
        }

        return Inertia::render('Billing/Index', $this->pageData->for(
            $team,
            $checkoutResult,
        ));
    }

    /**
     * Cashier normally creates the local subscription from the Stripe webhook.
     * If the webhook is delayed or never delivered, this fallback mirrors the
     * paid subscription so the team is not left on the free plan and does not
     * pay twice by starting another checkout.
     */
    private function syncMissingSubscription(Team $team): void
    {
        if ($team->subscribed('default')) {
            return;
        }

        try {
            $this->syncTeamSubscription->handle($team);

            $team->unsetRelation('subscriptions');
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function checkout(CheckoutRequest $request): SymfonyResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('manageBilling', $team);

        $this->syncMissingSubscription($team);

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
            $url = $this->createCheckoutSession->handle($team, $plan);
        } catch (ApiErrorException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'plan' => 'No se pudo iniciar el pago con Stripe. Intenta de nuevo.',
            ]);
        }

        return Inertia::location($url);
    }

    public function portal(Request $request): SymfonyResponse
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
            $redirect = $team->redirectToBillingPortal(route('billing.index', [
                'current_team' => $team->slug,
            ]));

            return Inertia::location($redirect->getTargetUrl());
        } catch (ApiErrorException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'billing' => 'No se pudo abrir el portal de facturación.',
            ]);
        }
    }
}
