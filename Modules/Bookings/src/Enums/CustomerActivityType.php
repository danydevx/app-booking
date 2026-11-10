<?php

namespace Modules\Bookings\Enums;

enum CustomerActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Email = 'email';
    case Whatsapp = 'whatsapp';
    case Meeting = 'meeting';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Nota',
            self::Call => 'Llamada',
            self::Email => 'Correo',
            self::Whatsapp => 'WhatsApp',
            self::Meeting => 'Reunión',
            self::System => 'Sistema',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function userOptions(): array
    {
        return [
            ['value' => self::Note->value, 'label' => self::Note->label()],
            ['value' => self::Call->value, 'label' => self::Call->label()],
            ['value' => self::Email->value, 'label' => self::Email->label()],
            ['value' => self::Whatsapp->value, 'label' => self::Whatsapp->label()],
            ['value' => self::Meeting->value, 'label' => self::Meeting->label()],
        ];
    }
}
