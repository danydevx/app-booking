<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\CustomerTaskPriority;
use Modules\Bookings\Enums\CustomerTaskStatus;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;

final class CreateCustomerTask
{
    public function __construct(
        private readonly RecordCustomerActivity $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Customer $customer, User $actor, array $data): CustomerTask
    {
        $task = new CustomerTask([
            'title' => (string) $data['title'],
            'notes' => $data['notes'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'priority' => CustomerTaskPriority::from((string) ($data['priority'] ?? CustomerTaskPriority::Normal->value)),
            'assigned_to' => isset($data['assigned_to']) && $data['assigned_to'] !== ''
                ? (int) $data['assigned_to']
                : $actor->id,
        ]);
        $task->team_id = $customer->team_id;
        $task->customer_id = $customer->id;
        $task->created_by = $actor->id;
        $task->status = CustomerTaskStatus::Open;
        $task->save();

        $this->activity->handle(
            $customer,
            CustomerActivityType::System,
            "Tarea creada: {$task->title}.",
            $actor,
            null,
            ['customer_task_id' => $task->id],
        );

        return $task->refresh();
    }
}
