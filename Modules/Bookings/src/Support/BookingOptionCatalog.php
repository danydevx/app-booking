<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Enums\DepositMethod;
use Modules\Bookings\Enums\DepositStatus;
use Modules\Bookings\Enums\InquiryStatus;

final class BookingOptionCatalog
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function inquiryStatuses(): array
    {
        return collect(InquiryStatus::cases())
            ->map(fn (InquiryStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function guestRanges(): array
    {
        return collect((array) config('bookings.contact_guest_ranges', []))
            ->map(fn (string $rangeLabel, string $rangeValue): array => [
                'value' => $rangeValue,
                'label' => $rangeLabel,
            ])
            ->values()
            ->all();
    }

    public static function guestRangeLabel(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $label = config('bookings.contact_guest_ranges.'.$value);

        return is_string($label) ? $label : $value;
    }

    public static function inquirySourceLabel(string $source): string
    {
        return match ($source) {
            'listing_contact' => 'Ficha pública',
            'public_web' => 'Sitio público',
            default => $source,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function depositStatuses(): array
    {
        return collect(DepositStatus::cases())
            ->map(fn (DepositStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function depositMethods(): array
    {
        return collect(DepositMethod::cases())
            ->map(fn (DepositMethod $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
            ])
            ->all();
    }
}
