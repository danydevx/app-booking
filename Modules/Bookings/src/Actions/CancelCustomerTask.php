<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\CustomerTask;

final class CancelCustomerTask
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(CustomerTask $task, User $actor): CustomerTask
    {
        if ($task->status === CustomerTaskStatus::Cancelled) {
            return $task;
        }

        $task->forceFill([
            'status' => CustomerTaskStatus::Cancelled,
        ])->save();

        $this->activity->handle(
            $task->customer,
            CustomerActivityType::System,
            "Tarea cancelada: {$task->title}.",
            $actor,
            null,
            ['customer_task_id' => $task->id],
        );

        return $task->refresh();
    }
}
