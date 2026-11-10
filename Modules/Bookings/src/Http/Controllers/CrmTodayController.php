<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;
use Modules\Bookings\Support\CustomerTaskPresenter;

class CrmTodayController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $mine = $request->boolean('mine');
        $timezone = $team->timezone;
        $today = now($timezone)->startOfDay();
        $endOfToday = now($timezone)->endOfDay();
        $endOfWeek = now($timezone)->addDays(7)->endOfDay();

        $base = CustomerTask::query()
            ->where('team_id', $team->id)
            ->open()
            ->with(['customer', 'assignee', 'team'])
            ->when($mine, fn ($query) => $query->where('assigned_to', $request->user()->id));

        $overdue = $this->presentTasks(
            (clone $base)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $today)
                ->orderBy('due_at')
                ->limit(50)
                ->get(),
            $team,
        );

        $todayTasks = $this->presentTasks(
            (clone $base)
                ->whereBetween('due_at', [$today, $endOfToday])
                ->orderBy('due_at')
                ->limit(50)
                ->get(),
            $team,
        );

        $upcomingTasks = $this->presentTasks(
            (clone $base)
                ->whereBetween('due_at', [$endOfToday, $endOfWeek])
                ->orderBy('due_at')
                ->limit(50)
                ->get(),
            $team,
        );

        $upcomingBookings = BookingInquiry::query()
            ->with('listing')
            ->where('team_id', $team->id)
            ->where('status', InquiryStatus::Accepted)
            ->whereBetween('start_date', [
                now($timezone)->toDateString(),
                now($timezone)->addDays(7)->toDateString(),
            ])
            ->orderBy('start_date')
            ->limit(10)
            ->get()
            ->map(fn (BookingInquiry $inquiry): array => [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'listing' => $inquiry->listing->name,
                'start_date' => $inquiry->start_date->toDateString(),
                'end_date' => $inquiry->end_date->toDateString(),
                'show_url' => route('bookings.show', [
                    'current_team' => $team->slug,
                    'inquiry' => $inquiry->id,
                ]),
            ])
            ->all();

        $unassignedCustomers = Customer::query()
            ->where('team_id', $team->id)
            ->whereNull('archived_at')
            ->whereNull('assigned_to')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'show_url' => route('bookings.customers.show', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
            ])
            ->all();

        return Inertia::render('Bookings/Crm/Today', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'mine' => $mine,
            'overdue' => $overdue,
            'todayTasks' => $todayTasks,
            'upcomingTasks' => $upcomingTasks,
            'upcomingBookings' => $upcomingBookings,
            'unassignedCustomers' => $unassignedCustomers,
            'unassignedCount' => Customer::query()
                ->where('team_id', $team->id)
                ->whereNull('archived_at')
                ->whereNull('assigned_to')
                ->count(),
            'urls' => [
                'today' => route('bookings.today', ['current_team' => $team->slug]),
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    /**
     * @param  Collection<int, CustomerTask>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function presentTasks(Collection $tasks, Team $team): array
    {
        return $tasks
            ->map(fn (CustomerTask $task): array => [
                ...CustomerTaskPresenter::present($task, Gate::allows('complete', $task)),
                'customer_name' => $task->customer->name,
                'customer_url' => route('bookings.customers.show', [
                    'current_team' => $team->slug,
                    'customer' => $task->customer_id,
                ]),
            ])
            ->values()
            ->all();
    }

    private function upgradeRedirect(Team $team): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'El CRM de clientes está disponible en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }
}
