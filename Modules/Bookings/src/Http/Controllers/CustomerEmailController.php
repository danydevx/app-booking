<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Http\Requests\SendCustomerEmailRequest;
use Modules\Bookings\Mail\CustomerMessage;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;

class CustomerEmailController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function send(
        SendCustomerEmailRequest $request,
        string $currentTeam,
        Customer $customer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if ($customer->email_opt_out_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'El cliente canceló la suscripción de correos.',
            ]);
        }

        $validated = $request->validated();
        $listing = $this->listingFor($customer);
        $booking = $customer->inquiries()
            ->where('status', InquiryStatus::Accepted)
            ->orderByDesc('start_date')
            ->first();

        $replacements = [
            '{{nombre}}' => $customer->name,
            '{{correo}}' => $customer->email,
            '{{terraza}}' => $listing ? $listing->name : '',
            '{{fechas}}' => $booking
                ? $booking->start_date->format('d/m/Y').' al '.$booking->end_date->format('d/m/Y')
                : '',
            '{{empresa}}' => $team->name,
        ];

        $subject = strtr((string) $validated['subject'], $replacements);
        $body = strtr((string) $validated['body'], $replacements);
        $unsubscribeUrl = URL::signedRoute('public.customers.unsubscribe', [
            'customer' => $customer->id,
        ]);

        Mail::to($customer->email)->queue(new CustomerMessage(
            $subject,
            $body,
            $unsubscribeUrl,
            $customer->name,
        ));

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::Email,
            "Correo enviado: {$subject}.",
            $request->user(),
            null,
            [
                'template_id' => $validated['template_id'] ?? null,
                'subject' => $subject,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correo encolado para envío.']);

        return back();
    }

    public function optIn(
        Request $request,
        string $currentTeam,
        Customer $customer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->forceFill(['email_opt_out_at' => null])->save();

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            'El cliente volvió a autorizar correos.',
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correo reactivado.']);

        return back();
    }

    private function listingFor(Customer $customer): ?Listing
    {
        $inquiry = $customer->inquiries()
            ->with('listing')
            ->orderByDesc('start_date')
            ->first();

        if ($inquiry !== null) {
            return $inquiry->listing;
        }

        return $customer->listing;
    }

    private function ensureOwned(Customer $customer, int $teamId): void
    {
        abort_unless($customer->team_id === $teamId, 404);
    }

    private function upgradeRedirect(Team $team): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'El CRM de clientes está disponible en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }
}
