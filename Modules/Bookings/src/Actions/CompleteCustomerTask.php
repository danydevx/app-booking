<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\CustomerTask;

final class CompleteCustomerTask
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    public function handle(CustomerTask $task, User $actor): CustomerTask
    {
        if ($task->status === CustomerTaskStatus::Done) {
            return $task;
        }

        $task->forceFill([
            'status' => CustomerTaskStatus::Done,
            'completed_at' => now(),
        ])->save();

        $this->activity->handle(
            $task->customer,
            CustomerActivityType::System,
            "Tarea completada: {$task->title}.",
            $actor,
            null,
            ['customer_task_id' => $task->id],
        );

        return $task->refresh();
    }
}
