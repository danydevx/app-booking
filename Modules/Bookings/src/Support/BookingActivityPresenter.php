<?php

namespace Modules\Bookings\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

final class BookingActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Activity $activity): array
    {
        $properties = $activity->properties?->toArray() ?? [];
        $causer = $activity->causer;

        return [
            'id' => $activity->id,
            'event' => $activity->description,
            'label' => self::label($activity->description),
            'summary' => self::summary($activity->description, $properties),
            'causer' => $causer instanceof User ? $causer->name : null,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    private static function label(string $event): string
    {
        return match ($event) {
            'booking.created' => 'Solicitud recibida',
            'booking.created_manual' => 'Reserva manual creada',
            'booking.contacted' => 'Solicitud contactada',
            'booking.accepted' => 'Reserva aceptada',
            'booking.rescheduled' => 'Reserva reprogramada',
            'booking.updated' => 'Datos actualizados',
            'booking.declined' => 'Solicitud rechazada',
            'booking.cancelled' => 'Reserva cancelada',
            default => 'Cambio en la reserva',
        };
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private static function summary(string $event, array $properties): ?string
    {
        if ($event === 'booking.rescheduled') {
            $oldStart = self::date($properties['old_start_date'] ?? null);
            $oldEnd = self::date($properties['old_end_date'] ?? null);
            $newStart = self::date($properties['start_date'] ?? null);
            $newEnd = self::date($properties['end_date'] ?? null);

            if ($oldStart !== null && $oldEnd !== null && $newStart !== null && $newEnd !== null) {
                return "{$oldStart} al {$oldEnd} → {$newStart} al {$newEnd}";
            }
        }

        $start = self::date($properties['start_date'] ?? null);
        $end = self::date($properties['end_date'] ?? null);

        if ($start !== null && $end !== null) {
            return "{$start} al {$end}";
        }

        return null;
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }
}
