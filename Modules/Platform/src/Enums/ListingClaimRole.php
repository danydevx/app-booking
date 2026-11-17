<?php

namespace Modules\Platform\Enums;

enum ListingClaimRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Representative = 'representative';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dueño / dueña',
            self::Manager => 'Administrador / administradora',
            self::Representative => 'Representante autorizado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
