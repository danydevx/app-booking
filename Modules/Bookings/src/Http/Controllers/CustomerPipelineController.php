<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\ChangeCustomerPipelineStage;
use Modules\Bookings\Enums\CustomerPipelineStage;
use Modules\Bookings\Http\Requests\UpdateCustomerPipelineStageRequest;
use Modules\Bookings\Models\Customer;

class CustomerPipelineController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function update(
        UpdateCustomerPipelineStageRequest $request,
        string $currentTeam,
        Customer $customer,
        ChangeCustomerPipelineStage $changeStage,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        abort_unless($customer->team_id === $team->id, 404);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $changeStage->handle(
            $customer,
            $request->user(),
            CustomerPipelineStage::from((string) $request->validated('stage')),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Etapa actualizada.',
        ]);

        return back();
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
