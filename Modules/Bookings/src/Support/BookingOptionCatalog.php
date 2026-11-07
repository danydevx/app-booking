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
