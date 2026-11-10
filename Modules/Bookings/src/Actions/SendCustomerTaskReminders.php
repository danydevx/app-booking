<?php

namespace Modules\Bookings\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Bookings\Models\CustomerTask;
use Modules\Bookings\Notifications\CustomerTaskDigest;

final class SendCustomerTaskReminders
{
    public function handle(bool $dryRun = false): int
    {
        $tasks = CustomerTask::query()
            ->with(['customer', 'assignee', 'team'])
            ->open()
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDay())
            ->where(fn ($query) => $query
                ->whereNull('last_reminded_at')
                ->orWhere('last_reminded_at', '<', now()->startOfDay()))
            ->orderBy('due_at')
            ->get();

        $dueTasks = $tasks
            ->filter(function (CustomerTask $task): bool {
                $timezone = $task->team->timezone;
                $today = now($timezone)->toDateString();

                return $task->due_at
                    ->timezone($timezone)
                    ->toDateString() <= $today;
            })
            ->groupBy(fn (CustomerTask $task): string => "{$task->team_id}:{$task->assigned_to}");

        $notified = 0;

        $dueTasks->each(function (Collection $group) use ($dryRun, &$notified): void {
            /** @var CustomerTask $first */
            $first = $group->first();
            $assignee = $first->assignee;

            if (! $assignee instanceof User) {
                return;
            }

            $notified++;

            if ($dryRun) {
                return;
            }

            $timezone = $first->team->timezone;

            $assignee->notify(new CustomerTaskDigest(
                $first->team->name,
                $group->map(fn (CustomerTask $task): array => [
                    'title' => $task->title,
                    'customer' => $task->customer->name,
                    'due' => $task->due_at->timezone($timezone)->format('d/m/Y H:i'),
                    'overdue' => $task->due_at->timezone($timezone)->toDateString() < now($timezone)->toDateString(),
                ])->values()->all(),
                route('bookings.today', ['current_team' => $first->team->slug]),
            ));

            CustomerTask::query()
                ->whereIn('id', $group->pluck('id'))
                ->update(['last_reminded_at' => now()]);
        });

        return $notified;
    }
}
