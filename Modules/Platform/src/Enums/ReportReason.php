<?php

namespace Modules\Platform\Enums;

enum ReportReason: string
{
    case Fraud = 'fraud';
    case Scam = 'scam';
    case Inaccurate = 'inaccurate';
    case Offensive = 'offensive';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fraud => 'Fraude o cobro indebido',
            self::Scam => 'Posible estafa',
            self::Inaccurate => 'Información falsa o desactualizada',
            self::Offensive => 'Contenido ofensivo',
            self::Other => 'Otro motivo',
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
