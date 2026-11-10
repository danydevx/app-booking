<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\CustomerQuote;

final class CustomerQuotePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(CustomerQuote $quote, string $teamSlug): array
    {
        $status = $quote->status;

        return [
            'id' => $quote->id,
            'status' => $status->value,
            'status_label' => $status->label(),
            'start_date' => $quote->start_date->toDateString(),
            'end_date' => $quote->end_date->toDateString(),
            'start_date_label' => $quote->start_date->format('d/m/Y'),
            'end_date_label' => $quote->end_date->format('d/m/Y'),
            'event_type' => $quote->event_type,
            'guests' => $quote->guests,
            'subtotal_cents' => $quote->subtotal_cents,
            'deposit_cents' => $quote->deposit_cents,
            'subtotal_formatted' => '$'.number_format($quote->subtotal_cents / 100, 2),
            'deposit_formatted' => '$'.number_format($quote->deposit_cents / 100, 2),
            'currency' => $quote->currency,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'expires_at' => $quote->expires_at?->toIso8601String(),
            'sent_at' => $quote->sent_at?->toIso8601String(),
            'accepted_at' => $quote->accepted_at?->toIso8601String(),
            'declined_at' => $quote->declined_at?->toIso8601String(),
            'converted_inquiry_id' => $quote->converted_inquiry_id,
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
            ],
            'public_url' => $quote->publicUrl(),
            'show_url' => route('bookings.quotes.show', [
                'current_team' => $teamSlug,
                'quote' => $quote->id,
            ]),
            'update_url' => route('bookings.quotes.update', [
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
            'can_edit' => $status->isEditable(),
            'can_send' => in_array($status, [QuoteStatus::Draft, QuoteStatus::Sent], true),
            'can_convert' => $status === QuoteStatus::Accepted && $quote->converted_inquiry_id === null,
            'created_at' => $quote->created_at?->toIso8601String(),
        ];
    }
}
