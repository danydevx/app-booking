<?php

namespace Modules\Bookings\Enums;

enum AvailabilityRecurrence: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Biweekly => 'Quincenal',
            self::Monthly => 'Mensual',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $recurrence): array => [
                'value' => $recurrence->value,
                'label' => $recurrence->label(),
            ],
            self::cases(),
        );
    }
}
