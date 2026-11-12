<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Models\CustomerQuoteItem;
use Modules\Bookings\Models\CustomerQuoteStatusHistory;

final class CustomerQuotePresenter
{
    private const UNIT_LABELS = [
        'hour' => 'Hora',
        'day' => 'Día',
        'event' => 'Evento',
        'service' => 'Servicio',
        'piece' => 'Pieza',
        'person' => 'Persona',
    ];

    private const ITEM_TYPE_LABELS = [
        'rental' => 'Renta de terraza',
        'service' => 'Servicio',
        'package' => 'Paquete',
        'product' => 'Producto o equipamiento',
        'custom' => 'Otro',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function present(CustomerQuote $quote, string $teamSlug): array
    {
        $quote->loadMissing(['customer', 'listing', 'items', 'statusHistories.changedBy']);

        $status = $quote->status;
        $hasItems = $quote->items->isNotEmpty();
        $calculation = app(QuoteCalculator::class)->calculate(
            $quote->items->pluck('amount_cents')->all(),
            (string) $quote->tax_mode,
            (int) $quote->tax_rate_bps,
            (string) $quote->discount_type,
            (int) $quote->discount_value,
        );
        $totals = $hasItems
            ? $calculation
            : [
                'subtotal_cents' => $quote->subtotal_cents,
                'tax_base_cents' => max(0, $quote->subtotal_cents - $quote->discount_cents),
                'discount_cents' => $quote->discount_cents,
                'tax_cents' => $quote->tax_cents,
                'total_cents' => $quote->totalCents(),
            ];

        $folio = $quote->folio ?? 'COT-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT);

        return [
            'id' => $quote->id,
            'folio' => $folio,
            'status' => $status->value,
            'status_label' => $status->label(),
            'start_date' => $quote->start_date->toDateString(),
            'end_date' => $quote->end_date->toDateString(),
            'start_date_label' => $quote->start_date->format('d/m/Y'),
            'end_date_label' => $quote->end_date->format('d/m/Y'),
            'rental_mode' => $quote->rental_mode ?? 'fixed',
            'start_time' => self::time($quote->start_time),
            'end_time' => self::time($quote->end_time),
            'billing_quantity' => $quote->billing_quantity,
            'event_type' => $quote->event_type,
            'guests' => $quote->guests,
            'subtotal_cents' => $totals['subtotal_cents'],
            'tax_base_cents' => $totals['tax_base_cents'],
            'discount_type' => $quote->discount_type ?? 'none',
            'discount_value' => $quote->discount_value,
            'discount_cents' => $totals['discount_cents'],
            'tax_mode' => $quote->tax_mode ?? 'none',
            'tax_rate_bps' => $quote->tax_rate_bps,
            'tax_cents' => $totals['tax_cents'],
            'total_cents' => $totals['total_cents'],
            'deposit_cents' => $quote->deposit_cents,
            'subtotal_formatted' => self::money($totals['subtotal_cents'], $quote->currency),
            'discount_formatted' => self::money($totals['discount_cents'], $quote->currency),
            'tax_base_formatted' => self::money($totals['tax_base_cents'], $quote->currency),
            'tax_formatted' => self::money($totals['tax_cents'], $quote->currency),
            'total_formatted' => self::money($totals['total_cents'], $quote->currency),
            'deposit_formatted' => self::money($quote->deposit_cents, $quote->currency),
            'currency' => $quote->currency,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'expires_at' => $quote->expires_at?->toIso8601String(),
            'issued_at' => $quote->issued_at?->toIso8601String(),
            'pdf_generated_at' => $quote->pdf_generated_at?->toIso8601String(),
            'sent_at' => $quote->sent_at?->toIso8601String(),
            'accepted_at' => $quote->accepted_at?->toIso8601String(),
            'declined_at' => $quote->declined_at?->toIso8601String(),
            'cancelled_at' => $quote->cancelled_at?->toIso8601String(),
            'converted_inquiry_id' => $quote->converted_inquiry_id,
            'items' => $quote->items
                ->map(fn (CustomerQuoteItem $item): array => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'type_label' => self::ITEM_TYPE_LABELS[$item->type] ?? $item->type,
                    'description' => $item->description,
                    'details' => $item->details,
                    'unit' => $item->unit,
                    'unit_label' => self::UNIT_LABELS[$item->unit] ?? $item->unit,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'amount_cents' => $item->amount_cents,
                    'unit_price_formatted' => self::money($item->unit_price_cents, $quote->currency),
                    'amount_formatted' => self::money($item->amount_cents, $quote->currency),
                    'sort_order' => $item->sort_order,
                ])
                ->values()
                ->all(),
            'history' => $quote->statusHistories
                ->map(fn (CustomerQuoteStatusHistory $entry): array => [
                    'id' => $entry->id,
                    'from_status' => $entry->from_status,
                    'from_status_label' => $entry->from_status
                        ? QuoteStatus::tryFrom($entry->from_status)?->label()
                        : null,
                    'to_status' => $entry->to_status,
                    'to_status_label' => QuoteStatus::tryFrom($entry->to_status)?->label(),
                    'note' => $entry->note,
                    'changed_by' => $entry->changedBy?->name,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'customer' => [
                'id' => $quote->customer->id,
                'name' => $quote->customer->name,
                'email' => $quote->customer->email,
                'phone' => $quote->customer->phone,
            ],
            'listing' => [
                'id' => $quote->listing->id,
                'name' => $quote->listing->name,
                'slug' => $quote->listing->slug,
                'logo_url' => $quote->listing->logoUrl(),
                'address' => $quote->listing->address,
                'city' => $quote->listing->city,
                'state' => $quote->listing->state,
                'contact_email' => $quote->listing->contact_email,
                'contact_phone' => $quote->listing->contact_phone,
            ],
            'public_url' => $quote->publicUrl(),
            'show_url' => route('bookings.quotes.show', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'edit_url' => route('bookings.quotes.edit', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'update_url' => route('bookings.quotes.update', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'preview_url' => route('bookings.quotes.preview', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'pdf_url' => route('bookings.quotes.pdf', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'send_url' => route('bookings.quotes.send', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'convert_url' => route('bookings.quotes.convert', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'issue_url' => route('bookings.quotes.issue', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'duplicate_url' => route('bookings.quotes.duplicate', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'cancel_url' => route('bookings.quotes.cancel', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'can_edit' => $status->isEditable(),
            'can_issue' => $status === QuoteStatus::Draft && $hasItems,
            'can_send' => $status === QuoteStatus::Sent,
            'can_convert' => $status === QuoteStatus::Accepted && $quote->converted_inquiry_id === null,
            'can_duplicate' => true,
            'can_cancel' => in_array($status, [QuoteStatus::Draft, QuoteStatus::Sent], true),
            'can_download_pdf' => filled($quote->pdf_path),
            'created_at' => $quote->created_at?->toIso8601String(),
        ];
    }

    public static function money(int $cents, string $currency = 'MXN'): string
    {
        return '$'.number_format($cents / 100, 2).' '.$currency;
    }

    private static function time(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return substr((string) $time, 0, 5);
    }
}
