<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Models\Customer;

final class ChangeCustomerPipelineStage
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(
        Customer $customer,
        User $actor,
        CustomerPipelineStage $stage,
    ): Customer {
        $previous = $customer->pipeline_stage;

        if ($previous === $stage) {
            return $customer;
        }

        $customer->forceFill(['pipeline_stage' => $stage])->save();

        $this->activity->handle(
            $customer,
            CustomerActivityType::System,
            "Etapa cambiada: {$previous->label()} → {$stage->label()}.",
            $actor,
        );

        return $customer->refresh();
    }
}
