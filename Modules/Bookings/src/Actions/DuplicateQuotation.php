<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Support\QuoteNumberGenerator;

final class DuplicateQuotation
{
    public function __construct(
        private readonly QuoteNumberGenerator $numbers,
        private readonly RecordQuoteStatusChange $statusHistory,
    ) {}

    public function handle(CustomerQuote $source, User $actor): CustomerQuote
    {
        $source->loadMissing('items');

        return DB::transaction(function () use ($source, $actor): CustomerQuote {
            $quote = new CustomerQuote;
            $quote->fill($source->only([
                'start_date',
                'end_date',
                'event_type',
                'guests',
                'subtotal_cents',
                'deposit_cents',
                'currency',
                'notes',
                'terms',
                'expires_at',
                'rental_mode',
                'start_time',
                'end_time',
                'billing_quantity',
                'tax_mode',
                'tax_rate_bps',
                'tax_cents',
                'discount_type',
                'discount_value',
                'discount_cents',
                'total_cents',
            ]));
            $quote->team_id = $source->team_id;
            $quote->customer_id = $source->customer_id;
            $quote->listing_id = $source->listing_id;
            $quote->created_by = $actor->id;
            $quote->folio = $this->numbers->next($source->team);
            $quote->status = QuoteStatus::Draft;
            $quote->save();

            foreach ($source->items as $index => $item) {
                $copy = new CustomerQuoteItem([
                    'type' => $item->type,
                    'description' => $item->description,
                    'details' => $item->details,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'amount_cents' => $item->amount_cents,
                    'sort_order' => $item->sort_order ?? $index,
                ]);
                $copy->customer_quote_id = $quote->id;
                $copy->team_id = $quote->team_id;
                $copy->save();
            }

            $this->statusHistory->handle(
                $quote,
                null,
                QuoteStatus::Draft,
                $actor,
                'Cotización duplicada.',
            );

            return $quote->refresh()->load('items');
        });
    }
}
