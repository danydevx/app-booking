<?php

namespace Modules\Platform\Enums;

enum ReportResolution: string
{
    case ListingSuspended = 'listing_suspended';
    case NoViolation = 'no_violation';
    case InformationOnly = 'information_only';

    public function label(): string
    {
        return match ($this) {
            self::ListingSuspended => 'Terraza suspendida',
            self::NoViolation => 'Sin infracción',
            self::InformationOnly => 'Solo informativo',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
