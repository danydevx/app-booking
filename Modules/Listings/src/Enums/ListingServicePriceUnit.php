<?php

namespace Modules\Listings\Enums;

enum ListingServicePriceUnit: string
{
    case Event = 'event';
    case Person = 'person';
    case Hour = 'hour';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Por evento',
            self::Person => 'Por persona',
            self::Hour => 'Por hora',
            self::Night => 'Por noche',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $unit): array => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ],
            self::cases(),
        );
    }
}
