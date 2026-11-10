<?php

namespace Modules\Bookings\Enums;

enum CustomerPipelineStage: string
{
    case Lead = 'lead';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Prospecto',
            self::Contacted => 'Contactado',
            self::Qualified => 'Calificado',
            self::Proposal => 'Propuesta',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $stage): array => [
                'value' => $stage->value,
                'label' => $stage->label(),
            ],
            self::cases(),
        );
    }
}
