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
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Http\Requests\StoreCustomerActivityRequest;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerActivity;

class CustomerActivityController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function store(
        StoreCustomerActivityRequest $request,
        string $currentTeam,
        Customer $customer,
        RecordCustomerActivity $recordActivity,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $data = $request->validated();

        $recordActivity->handle(
            $customer,
            CustomerActivityType::from((string) $data['type']),
            (string) $data['body'],
            $request->user(),
            isset($data['occurred_at']) ? (string) $data['occurred_at'] : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Actividad registrada.',
        ]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        Customer $customer,
        CustomerActivity $activity,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        abort_unless(
            $activity->team_id === $team->id
            && $activity->customer_id === $customer->id,
            404,
        );

        Gate::authorize('delete', $activity);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $activity->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Actividad eliminada.',
        ]);

        return back();
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
