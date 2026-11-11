<?php

namespace Modules\Listings\Enums;

enum ListingServiceProvider: string
{
    case Own = 'own';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'Proveedor propio',
            self::External => 'Proveedor externo',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
            ],
            self::cases(),
        );
    }
}
