<?php

namespace Modules\Bookings\Support;

use Modules\Bookings\Models\CustomerTask;

final class CustomerTaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(CustomerTask $task, bool $canManage): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'notes' => $task->notes,
            'due_at' => $task->due_at?->toIso8601String(),
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'assignee' => $task->assignee?->name,
            'assignee_id' => $task->assigned_to,
            'is_overdue' => $task->due_at !== null
                && $task->status->value === 'open'
                && $task->due_at->isPast(),
            'can_manage' => $canManage,
            'update_url' => route('bookings.customers.tasks.update', [
                'current_team' => $task->team->slug,
                'customer' => $task->customer_id,
                'task' => $task->id,
            ]),
            'complete_url' => route('bookings.customers.tasks.complete', [
                'current_team' => $task->team->slug,
                'customer' => $task->customer_id,
                'task' => $task->id,
            ]),
            'cancel_url' => route('bookings.customers.tasks.cancel', [
                'current_team' => $task->team->slug,
                'customer' => $task->customer_id,
                'task' => $task->id,
            ]),
        ];
    }
}
