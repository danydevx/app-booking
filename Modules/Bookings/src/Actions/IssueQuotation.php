<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Support\QuotationPdfRenderer;

final class IssueQuotation
{
    public function __construct(
        private readonly QuotationPdfRenderer $pdf,
        private readonly RecordQuoteStatusChange $statusHistory,
    ) {}

    public function handle(CustomerQuote $quote, User $actor): CustomerQuote
    {
        if ($quote->status !== QuoteStatus::Draft) {
            abort(422, 'Solo las cotizaciones en borrador se pueden emitir.');
        }

        $quote->loadMissing(['items', 'customer', 'listing.team', 'team']);

        if ($quote->items->isEmpty()) {
            abort(422, 'Agrega al menos un concepto antes de emitir la cotización.');
        }

        $issuer = $this->pdf->preserveLogo($quote, $this->pdf->issuer($quote));
        $customer = $this->pdf->customer($quote);

        $quote->forceFill([
            'issuer_snapshot' => $issuer,
            'customer_snapshot' => $customer,
            'listing_snapshot' => [
                'id' => $quote->listing->id,
                'name' => $quote->listing->name,
                'address' => $quote->listing->address,
                'city' => $quote->listing->city,
                'state' => $quote->listing->state,
                'logo_path' => $issuer['logo_path'] ?? null,
            ],
            'expires_at' => $quote->expires_at ?? now()->addDays(
                (int) config('bookings.quotes.valid_days', 15),
            ),
        ])->save();

        $pdf = $this->pdf->stream($quote);
        $path = $this->pdf->store($quote, $pdf);

        return DB::transaction(function () use ($quote, $actor, $path): CustomerQuote {
            $previous = $quote->status;

            $quote->forceFill([
                'status' => QuoteStatus::Sent,
                'issued_at' => now(),
                'sent_at' => now(),
                'pdf_path' => $path,
                'pdf_generated_at' => now(),
            ])->save();

            $this->statusHistory->handle(
                $quote,
                $previous,
                QuoteStatus::Sent,
                $actor,
                'Cotización emitida.',
            );

            return $quote->refresh();
        });
    }
}
