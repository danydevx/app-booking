<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;
use Modules\Bookings\Models\CustomerTask;

final class AnonymizeCustomer
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(Customer $customer, User $actor, string $reason): Customer
    {
        return DB::transaction(function () use ($customer, $actor, $reason): Customer {
            $placeholderEmail = "anonimizado+{$customer->id}@example.invalid";
            $placeholderName = "Cliente anonimizado #{$customer->id}";

            BookingInquiry::query()
                ->where('customer_id', $customer->id)
                ->update([
                    'name' => 'Cliente anonimizado',
                    'email' => $placeholderEmail,
                    'phone' => '0000000000',
                    'whatsapp_phone' => null,
                    'message' => null,
                    'ip_hash' => null,
                ]);

            CustomerActivity::query()
                ->where('customer_id', $customer->id)
                ->update([
                    'body' => '[Contenido eliminado por anonimización]',
                    'metadata' => null,
                ]);

            CustomerTask::query()
                ->where('customer_id', $customer->id)
                ->update([
                    'title' => 'Tarea de cliente anonimizado',
                    'notes' => null,
                    'status' => CustomerTaskStatus::Cancelled->value,
                    'completed_at' => null,
                ]);

            $customer->forceFill([
                'name' => $placeholderName,
                'email' => $placeholderEmail,
                'phone' => null,
                'whatsapp_phone' => null,
                'notes' => null,
                'anonymized_at' => now(),
                'archived_at' => now(),
            ])->save();

            $this->activity->handle(
                $customer,
                CustomerActivityType::System,
                "Cliente anonimizado. Motivo: {$reason}",
                $actor,
            );

            return $customer->refresh();
        });
    }
}
