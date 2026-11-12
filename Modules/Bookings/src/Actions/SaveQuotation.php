<?php

namespace Modules\Bookings\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Support\QuoteCalculator;
use Modules\Bookings\Support\QuoteNumberGenerator;
use Modules\Listings\Models\Listing;

final class SaveQuotation
{
    public function __construct(
        private readonly QuoteCalculator $calculator,
        private readonly QuoteNumberGenerator $numbers,
        private readonly RecordQuoteStatusChange $statusHistory,
        private readonly CreateCustomer $createCustomer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        Team $team,
        User $actor,
        array $data,
        ?CustomerQuote $quote = null,
    ): CustomerQuote {
        return DB::transaction(function () use ($team, $actor, $data, $quote): CustomerQuote {
            $listing = Listing::query()
                ->where('team_id', $team->id)
                ->findOrFail((int) $data['listing_id']);

            $customer = $this->resolveCustomer($team, $listing, $actor, $data);

            $isNew = $quote === null;

            if ($quote === null) {
                $quote = new CustomerQuote([
                    'folio' => $this->numbers->next($team),
                    'currency' => (string) config('bookings.quotes.currency', 'MXN'),
                ]);
                $quote->team_id = $team->id;
                $quote->listing_id = $listing->id;
                $quote->customer_id = $customer->id;
                $quote->created_by = $actor->id;
                $quote->status = QuoteStatus::Draft;
            } else {
                abort_unless($quote->team_id === $team->id, 404);

                if ($quote->status !== QuoteStatus::Draft) {
                    abort(422, 'Solo las cotizaciones en borrador se pueden editar.');
                }
            }

            $quote->fill([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rental_mode' => $data['rental_mode'] ?? 'fixed',
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'billing_quantity' => $data['billing_quantity'] ?? null,
                'event_type' => $data['event_type'] ?? null,
                'guests' => $data['guests'] ?? null,
                'tax_mode' => $data['tax_mode'] ?? 'none',
                'tax_rate_bps' => (int) ($data['tax_rate_bps']
                    ?? $quote->tax_rate_bps
                    ?? config('bookings.quotes.tax_rate_bps', 1600)),
                'discount_type' => $data['discount_type'] ?? 'none',
                'discount_value' => (int) ($data['discount_value'] ?? 0),
                'deposit_cents' => (int) ($data['deposit_cents'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
            $quote->listing_id = $listing->id;
            $quote->customer_id = $customer->id;
            $quote->converted_inquiry_id = $data['converted_inquiry_id'] ?? null;
            $quote->save();

            $quote->items()->delete();

            $itemAmounts = [];

            foreach (array_values((array) $data['items']) as $index => $item) {
                $quantity = (int) $item['quantity'];
                $unitPrice = (int) $item['unit_price_cents'];
                $amount = $quantity * $unitPrice;
                $itemAmounts[] = $amount;

                $line = new CustomerQuoteItem([
                    'type' => $item['type'],
                    'description' => $item['description'],
                    'details' => $item['details'] ?? null,
                    'unit' => $item['unit'],
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPrice,
                    'amount_cents' => $amount,
                    'sort_order' => (int) ($item['sort_order'] ?? $index),
                ]);
                $line->customer_quote_id = $quote->id;
                $line->team_id = $team->id;
                $line->save();
            }

            $totals = $this->calculator->calculate(
                $itemAmounts,
                (string) $quote->tax_mode,
                (int) $quote->tax_rate_bps,
                (string) $quote->discount_type,
                (int) $quote->discount_value,
            );

            $quote->forceFill([
                'subtotal_cents' => $totals['subtotal_cents'],
                'discount_cents' => $totals['discount_cents'],
                'tax_cents' => $totals['tax_cents'],
                'total_cents' => $totals['total_cents'],
            ])->save();

            if ($isNew) {
                $this->statusHistory->handle(
                    $quote,
                    null,
                    QuoteStatus::Draft,
                    $actor,
                    'Cotización creada.',
                );
            }

            return $quote->refresh()->load(['items', 'customer', 'listing', 'statusHistories']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(
        Team $team,
        Listing $listing,
        User $actor,
        array $data,
    ): Customer {
        if (filled($data['customer_id'] ?? null)) {
            return Customer::query()
                ->where('team_id', $team->id)
                ->findOrFail((int) $data['customer_id']);
        }

        $new = (array) ($data['new_customer'] ?? []);

        $customer = $this->createCustomer->handle($team, [
            'name' => (string) ($new['name'] ?? ''),
            'email' => (string) ($new['email'] ?? ''),
            'phone' => $new['phone'] ?? null,
            'notes' => $new['notes'] ?? null,
            'assigned_to' => $actor->id,
        ], $listing);

        app(RecordCustomerActivity::class)->handle(
            $customer,
            CustomerActivityType::System,
            'Cliente creado desde una cotización.',
            $actor,
        );

        return $customer;
    }
}
