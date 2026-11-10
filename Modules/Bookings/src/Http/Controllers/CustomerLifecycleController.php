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
use Modules\Bookings\Actions\AnonymizeCustomer;
use Modules\Bookings\Actions\ArchiveCustomer;
use Modules\Bookings\Actions\MergeCustomers;
use Modules\Bookings\Http\Requests\AnonymizeCustomerRequest;
use Modules\Bookings\Http\Requests\MergeCustomerRequest;
use Modules\Bookings\Models\Customer;

class CustomerLifecycleController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function merge(
        MergeCustomerRequest $request,
        string $currentTeam,
        Customer $customer,
        MergeCustomers $mergeCustomers,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('merge', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $target = Customer::query()
            ->where('team_id', $team->id)
            ->findOrFail((int) $request->validated('target_id'));

        $merged = $mergeCustomers->handle(
            $customer,
            $target,
            $request->user(),
            (string) $request->validated('reason'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Clientes fusionados.',
        ]);

        return to_route('bookings.customers.show', [
            'current_team' => $team->slug,
            'customer' => $merged->id,
        ]);
    }

    public function archive(
        Request $request,
        string $currentTeam,
        Customer $customer,
        ArchiveCustomer $archiveCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('archive', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $archiveCustomer->handle($customer, $request->user(), true);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cliente archivado.',
        ]);

        return to_route('bookings.customers.index', ['current_team' => $team->slug]);
    }

    public function restore(
        Request $request,
        string $currentTeam,
        Customer $customer,
        ArchiveCustomer $archiveCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('archive', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $archiveCustomer->handle($customer, $request->user(), false);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cliente restaurado.',
        ]);

        return to_route('bookings.customers.show', [
            'current_team' => $team->slug,
            'customer' => $customer->id,
        ]);
    }

    public function anonymize(
        AnonymizeCustomerRequest $request,
        string $currentTeam,
        Customer $customer,
        AnonymizeCustomer $anonymize,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('anonymize', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $anonymize->handle(
            $customer,
            $request->user(),
            (string) $request->validated('reason'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cliente anonimizado.',
        ]);

        return to_route('bookings.customers.index', ['current_team' => $team->slug]);
    }

    private function ensureOwned(Customer $customer, int $teamId): void
    {
        abort_unless($customer->team_id === $teamId, 404);
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
