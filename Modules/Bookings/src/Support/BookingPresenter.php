<?php

namespace Modules\Bookings\Support;

use App\Support\WhatsApp;
use Modules\Bookings\Models\BookingDeposit;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTag;

final class BookingPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function inquiryCard(BookingInquiry $inquiry, string $teamSlug): array
    {
        return [
            'id' => $inquiry->id,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'start_date' => $inquiry->start_date->toDateString(),
            'end_date' => $inquiry->end_date->toDateString(),
            'start_date_label' => $inquiry->start_date->format('d/m/Y'),
            'end_date_label' => $inquiry->end_date->format('d/m/Y'),
            'guests' => $inquiry->guests,
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'created_at' => $inquiry->created_at?->toISOString(),
            'listing' => [
                'id' => $inquiry->listing->id,
                'name' => $inquiry->listing->name,
                'slug' => $inquiry->listing->slug,
            ],
            'customer_id' => $inquiry->customer_id,
            'show_url' => route('bookings.show', [
                'current_team' => $teamSlug,
                'inquiry' => $inquiry->id,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function inquiryDetail(BookingInquiry $inquiry, string $teamSlug): array
    {
        return [
            ...self::inquiryCard($inquiry, $teamSlug),
            'whatsapp_phone' => $inquiry->whatsapp_phone,
            'event_type' => $inquiry->event_type,
            'message' => $inquiry->message,
            'source' => $inquiry->source,
            'source_detail' => $inquiry->source_detail,
            'utm_source' => $inquiry->utm_source,
            'utm_medium' => $inquiry->utm_medium,
            'utm_campaign' => $inquiry->utm_campaign,
            'utm_term' => $inquiry->utm_term,
            'utm_content' => $inquiry->utm_content,
            'referrer' => $inquiry->referrer,
            'portal_url' => $inquiry->portalUrl(),
            'customer_confirmed_at' => $inquiry->customer_confirmed_at?->toISOString(),
            'feedback_rating' => $inquiry->feedback_rating,
            'feedback_comment' => $inquiry->feedback_comment,
            'feedback_at' => $inquiry->feedback_at?->toISOString(),
            'consent_at' => $inquiry->consent_at?->toISOString(),
            'contacted_at' => $inquiry->contacted_at?->toISOString(),
            'accepted_at' => $inquiry->accepted_at?->toISOString(),
            'declined_at' => $inquiry->declined_at?->toISOString(),
            'cancelled_at' => $inquiry->cancelled_at?->toISOString(),
            'public_listing_url' => route('public.listings.show', [
                'listing' => $inquiry->listing->slug,
            ]),
            'whatsapp_url' => self::whatsappUrl(
                $inquiry->whatsapp_phone ?: $inquiry->phone,
                "Hola {$inquiry->name}, te contacto por tu solicitud para {$inquiry->listing->name}.",
            ),
            'customer' => $inquiry->customer
                ? self::customerCard($inquiry->customer, $teamSlug)
                : null,
            'deposits' => $inquiry->deposits
                ->map(fn (BookingDeposit $deposit): array => self::deposit($deposit, $teamSlug))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function customerCard(
        Customer $customer,
        string $teamSlug,
        ?string $listingSlug = null,
    ): array {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'whatsapp_phone' => $customer->whatsapp_phone,
            'notes' => $customer->notes,
            'assigned_to' => $customer->assigned_to,
            'assignee' => $customer->relationLoaded('assignee')
                ? $customer->assignee?->name
                : null,
            'email_opt_out_at' => $customer->email_opt_out_at?->toIso8601String(),
            'pipeline_stage' => $customer->pipeline_stage->value,
            'pipeline_stage_label' => $customer->pipeline_stage->label(),
            'is_archived' => $customer->archived_at !== null,
            'is_anonymized' => $customer->anonymized_at !== null,
            'inquiries_count' => (int) ($customer->inquiries_count ?? 0),
            'accepted_inquiries_count' => (int) ($customer->accepted_inquiries_count ?? 0),
            'open_tasks_count' => (int) ($customer->open_tasks_count ?? 0),
            'tags' => $customer->relationLoaded('tags')
                ? $customer->tags
                    ->map(fn (CustomerTag $tag): array => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                    ])
                    ->values()
                    ->all()
                : [],
            'overdue_tasks_count' => (int) ($customer->overdue_tasks_count ?? 0),
            'created_at' => $customer->created_at?->toISOString(),
            'show_url' => $listingSlug
                ? route('bookings.listing.customers.show', [
                    'current_team' => $teamSlug,
                    'listing' => $listingSlug,
                    'customer' => $customer->id,
                ])
                : route('bookings.customers.show', [
                    'current_team' => $teamSlug,
                    'customer' => $customer->id,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function deposit(BookingDeposit $deposit, string $teamSlug): array
    {
        return [
            'id' => $deposit->id,
            'amount' => $deposit->amount(),
            'amount_formatted' => '$'.number_format($deposit->amount(), 2),
            'currency' => $deposit->currency,
            'status' => $deposit->status->value,
            'status_label' => $deposit->status->label(),
            'method' => $deposit->method->value,
            'method_label' => $deposit->method->label(),
            'reference' => $deposit->reference,
            'received_at' => $deposit->received_at?->toDateString(),
            'notes' => $deposit->notes,
            'update_url' => route('bookings.deposits.update', [
                'current_team' => $teamSlug,
                'inquiry' => $deposit->booking_inquiry_id,
                'deposit' => $deposit->id,
            ]),
        ];
    }

    public static function whatsappUrl(?string $phone, string $message): ?string
    {
        return WhatsApp::url($phone, $message);
    }
}
