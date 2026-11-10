<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Models\CustomerTask;

final class UpdateCustomerTask
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(CustomerTask $task, User $actor, array $data): CustomerTask
    {
        $task->update([
            'title' => (string) $data['title'],
            'notes' => $data['notes'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'priority' => CustomerTaskPriority::from((string) $data['priority']),
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        $this->activity->handle(
            $task->customer,
            CustomerActivityType::System,
            "Tarea actualizada: {$task->title}.",
            $actor,
            null,
            ['customer_task_id' => $task->id],
        );

        return $task->refresh();
    }
}
