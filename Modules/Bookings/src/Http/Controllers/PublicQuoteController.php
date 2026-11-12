<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Actions\RecordQuoteStatusChange;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Notifications\CustomerQuoteAccepted;
use Modules\Bookings\Support\QuoteCalculator;

class PublicQuoteController extends Controller
{
    public function __construct(
        private readonly RecordCustomerActivity $activityLogger,
        private readonly RecordQuoteStatusChange $statusHistory,
    ) {}

    public function show(string $token): Response
    {
        $quote = $this->findQuote($token);

        if ($quote->status === QuoteStatus::Sent && $quote->isExpired()) {
            $previous = $quote->status;
            $quote->forceFill(['status' => QuoteStatus::Expired])->save();
            $this->statusHistory->handle($quote, $previous, QuoteStatus::Expired, null, 'Cotización vencida.');
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

        $previous = $quote->status;

        $quote->forceFill([
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now(),
        ])->save();

        $this->statusHistory->handle(
            $quote,
            $previous,
            QuoteStatus::Accepted,
            null,
            'Cotización aceptada por el cliente.',
        );

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

        $previous = $quote->status;

        $quote->forceFill([
            'status' => QuoteStatus::Declined,
            'declined_at' => now(),
        ])->save();

        $this->statusHistory->handle(
            $quote,
            $previous,
            QuoteStatus::Declined,
            null,
            'Cotización rechazada por el cliente.',
        );

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
            ->with(['customer', 'listing', 'team', 'items'])
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
        $calculation = app(QuoteCalculator::class)->calculate(
            $quote->items->pluck('amount_cents')->all(),
            (string) $quote->tax_mode,
            (int) $quote->tax_rate_bps,
            (string) $quote->discount_type,
            (int) $quote->discount_value,
        );

        $totals = $quote->items->isNotEmpty()
            ? $calculation
            : [
                'subtotal_cents' => $quote->subtotal_cents,
                'tax_base_cents' => max(0, $quote->subtotal_cents - $quote->discount_cents),
                'discount_cents' => $quote->discount_cents,
                'tax_cents' => $quote->tax_cents,
                'total_cents' => $quote->totalCents(),
            ];

        return [
            'id' => $quote->id,
            'folio' => $quote->folio,
            'status' => $quote->status->value,
            'status_label' => $quote->status->label(),
            'customer_name' => $quote->customer->name,
            'listing_name' => $quote->listing->name,
            'listing_logo_url' => $quote->listing->logoUrl(),
            'listing_location' => collect([$quote->listing->city, $quote->listing->state])
                ->filter()
                ->implode(', '),
            'start_date' => $quote->start_date->toDateString(),
            'end_date' => $quote->end_date->toDateString(),
            'start_date_label' => $quote->start_date->format('d/m/Y'),
            'end_date_label' => $quote->end_date->format('d/m/Y'),
            'event_type' => $quote->event_type,
            'guests' => $quote->guests,
            'items' => $quote->items->map(fn (CustomerQuoteItem $item): array => [
                'description' => $item->description,
                'details' => $item->details,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price_formatted' => '$'.number_format($item->unit_price_cents / 100, 2),
                'amount_formatted' => '$'.number_format($item->amount_cents / 100, 2),
            ])->values()->all(),
            'tax_mode' => $quote->tax_mode,
            'tax_rate_bps' => $quote->tax_rate_bps,
            'subtotal_formatted' => '$'.number_format($totals['subtotal_cents'] / 100, 2),
            'discount_formatted' => '$'.number_format($totals['discount_cents'] / 100, 2),
            'tax_formatted' => '$'.number_format($totals['tax_cents'] / 100, 2),
            'total_formatted' => '$'.number_format($totals['total_cents'] / 100, 2),
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
