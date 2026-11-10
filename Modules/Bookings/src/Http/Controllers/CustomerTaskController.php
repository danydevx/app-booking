<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\CancelCustomerTask;
use Modules\Bookings\Actions\CompleteCustomerTask;
use Modules\Bookings\Actions\CreateCustomerTask;
use Modules\Bookings\Actions\UpdateCustomerTask;
use Modules\Bookings\Http\Requests\StoreCustomerTaskRequest;
use Modules\Bookings\Http\Requests\UpdateCustomerTaskRequest;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTask;

class CustomerTaskController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function store(
        StoreCustomerTaskRequest $request,
        string $currentTeam,
        Customer $customer,
        CreateCustomerTask $createTask,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $createTask->handle($customer, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarea creada.',
        ]);

        return back();
    }

    public function update(
        UpdateCustomerTaskRequest $request,
        string $currentTeam,
        Customer $customer,
        CustomerTask $task,
        UpdateCustomerTask $updateTask,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);
        $this->ensureTaskBelongs($task, $customer, $team->id);

        Gate::authorize('update', $task);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $updateTask->handle($task, $request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarea actualizada.',
        ]);

        return back();
    }

    public function complete(
        Request $request,
        string $currentTeam,
        Customer $customer,
        CustomerTask $task,
        CompleteCustomerTask $completeTask,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);
        $this->ensureTaskBelongs($task, $customer, $team->id);

        Gate::authorize('complete', $task);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $completeTask->handle($task, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarea completada.',
        ]);

        return back();
    }

    public function cancel(
        Request $request,
        string $currentTeam,
        Customer $customer,
        CustomerTask $task,
        CancelCustomerTask $cancelTask,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);
        $this->ensureTaskBelongs($task, $customer, $team->id);

        Gate::authorize('cancel', $task);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $cancelTask->handle($task, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tarea cancelada.',
        ]);

        return back();
    }

    private function ensureOwned(Customer $customer, int $teamId): void
    {
        abort_unless($customer->team_id === $teamId, 404);
    }

    private function ensureTaskBelongs(CustomerTask $task, Customer $customer, int $teamId): void
    {
        abort_unless(
            $task->team_id === $teamId && $task->customer_id === $customer->id,
            404,
        );
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
