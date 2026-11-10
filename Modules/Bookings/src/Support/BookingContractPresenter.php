<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Models\BookingContract;

final class BookingContractPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(BookingContract $contract, string $teamSlug): array
    {
        return [
            'id' => $contract->id,
            'title' => $contract->title,
            'body' => $contract->body,
            'status' => $contract->status->value,
            'status_label' => $contract->status->label(),
            'sent_at' => $contract->sent_at?->toIso8601String(),
            'accepted_at' => $contract->accepted_at?->toIso8601String(),
            'accepted_by_name' => $contract->accepted_by_name,
            'can_accept' => $contract->canBeAccepted(),
            'can_edit' => $contract->isEditable(),
            'update_url' => route('bookings.contracts.update', [
                'current_team' => $teamSlug,
                'contract' => $contract->id,
            ]),
            'publish_url' => route('bookings.contracts.publish', [
                'current_team' => $teamSlug,
                'contract' => $contract->id,
            ]),
            'public_url' => route('public.bookings.portal.contract', [
                'token' => $contract->bookingInquiry->portal_token,
            ]),
            'accept_url' => route('public.bookings.portal.contract.accept', [
                'token' => $contract->bookingInquiry->portal_token,
            ]),
        ];
    }
}
