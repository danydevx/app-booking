<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Notifications\CustomerQuoteAccepted;

class PublicQuoteController extends Controller
{
    public function __construct(
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function show(string $token): Response
    {
        $quote = $this->findQuote($token);

        if ($quote->status === QuoteStatus::Sent && $quote->isExpired()) {
            $quote->forceFill(['status' => QuoteStatus::Expired])->save();
        }

        return Inertia::render('Bookings/Public/Quote', [
            'quote' => $this->present($quote),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $quote = $this->findQuote($token);

        if (! $this->canRespond($quote)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Esta cotización ya no está disponible para responder.',
            ]);

            return back();
        }

        $quote->forceFill([
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now(),
        ])->save();

        $this->activityLogger->handle(
            $quote->customer,
            CustomerActivityType::System,
            'Cotización aceptada por el cliente.',
            null,
            null,
            ['customer_quote_id' => $quote->id],
        );

        $owner = $quote->team->owner();

        if ($owner !== null && $owner->wantsInquiryEmails()) {
            $owner->notify(new CustomerQuoteAccepted($quote));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cotización aceptada. El propietario recibirá tu confirmación.',
        ]);

        return back();
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $quote = $this->findQuote($token);

        if (! $this->canRespond($quote)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Esta cotización ya no está disponible para responder.',
            ]);

            return back();
        }

        $quote->forceFill([
            'status' => QuoteStatus::Declined,
            'declined_at' => now(),
        ])->save();

        $this->activityLogger->handle(
            $quote->customer,
            CustomerActivityType::System,
            'Cotización rechazada por el cliente.',
            null,
            null,
            ['customer_quote_id' => $quote->id],
        );

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => 'Cotización rechazada. Gracias por avisarnos.',
        ]);

        return back();
    }

    private function findQuote(string $token): CustomerQuote
    {
        return CustomerQuote::query()
            ->with(['customer', 'listing', 'team'])
            ->where('public_token', $token)
            ->firstOrFail();
    }

    private function canRespond(CustomerQuote $quote): bool
    {
        return $quote->status === QuoteStatus::Sent && ! $quote->isExpired();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(CustomerQuote $quote): array
    {
        return [
            'id' => $quote->id,
            'status' => $quote->status->value,
            'status_label' => $quote->status->label(),
            'customer_name' => $quote->customer->name,
            'listing_name' => $quote->listing->name,
            'listing_location' => collect([$quote->listing->city, $quote->listing->state])
                ->filter()
                ->implode(', '),
            'start_date' => $quote->start_date->toDateString(),
            'end_date' => $quote->end_date->toDateString(),
            'start_date_label' => $quote->start_date->format('d/m/Y'),
            'end_date_label' => $quote->end_date->format('d/m/Y'),
            'event_type' => $quote->event_type,
            'guests' => $quote->guests,
            'subtotal_formatted' => '$'.number_format($quote->subtotal_cents / 100, 2),
            'deposit_formatted' => '$'.number_format($quote->deposit_cents / 100, 2),
            'currency' => $quote->currency,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'expires_at' => $quote->expires_at?->toIso8601String(),
            'accepted_at' => $quote->accepted_at?->toIso8601String(),
            'declined_at' => $quote->declined_at?->toIso8601String(),
            'can_respond' => $this->canRespond($quote),
            'accept_url' => route('public.quotes.accept', ['token' => $quote->public_token]),
            'decline_url' => route('public.quotes.decline', ['token' => $quote->public_token]),
        ];
    }
}
