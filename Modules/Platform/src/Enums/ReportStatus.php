<?php

namespace Modules\Platform\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Reviewing => 'En revisión',
            self::Resolved => 'Resuelto',
            self::Dismissed => 'Descartado',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Resolved || $this === self::Dismissed;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
