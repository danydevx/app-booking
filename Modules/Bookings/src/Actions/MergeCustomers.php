<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Models\Customer;

final class MergeCustomers
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(
        Customer $source,
        Customer $target,
        User $actor,
        string $reason,
    ): Customer {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'target_id' => 'Selecciona un cliente diferente.',
            ]);
        }

        if ($source->team_id !== $target->team_id) {
            throw ValidationException::withMessages([
                'target_id' => 'Los clientes deben pertenecer a la misma cuenta.',
            ]);
        }

        return DB::transaction(function () use ($source, $target, $actor, $reason): Customer {
            $tagIds = $source->tags()->pluck('customer_tags.id')->all();

            $source->inquiries()->update(['customer_id' => $target->id]);
            $source->activities()->update(['customer_id' => $target->id]);
            $source->tasks()->update(['customer_id' => $target->id]);

            if ($tagIds !== []) {
                $target->tags()->syncWithoutDetaching($tagIds);
                $source->tags()->detach();
            }

            if ($target->listing_id === null && $source->listing_id !== null) {
                $target->listing_id = $source->listing_id;
            }

            $target->fill(array_filter([
                'name' => $target->name,
                'email' => $target->email,
                'phone' => $target->phone ?: $source->phone,
                'whatsapp_phone' => $target->whatsapp_phone ?: $source->whatsapp_phone,
                'notes' => $target->notes ?: $source->notes,
            ], fn ($value): bool => $value !== null && $value !== ''))->save();

            $source->forceFill([
                'merged_into_id' => $target->id,
                'merged_at' => now(),
                'archived_at' => now(),
            ])->save();

            $this->activity->handle(
                $target,
                CustomerActivityType::System,
                "Cliente fusionado desde {$source->name} ({$source->email}). Motivo: {$reason}",
                $actor,
                null,
                ['merged_customer_id' => $source->id],
            );

            return $target->refresh();
        });
    }
}
