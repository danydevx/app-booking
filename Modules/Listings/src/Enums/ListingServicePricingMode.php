<?php

namespace Modules\Listings\Enums;

enum ListingServicePricingMode: string
{
    case Included = 'included';
    case Additional = 'additional';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Included => 'Incluido en la renta',
            self::Additional => 'Costo adicional',
            self::Quote => 'Precio a consultar',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode): array => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ],
            self::cases(),
        );
    }
}
