<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Http\Requests\ImportCustomersRequest;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Support\ImportCustomersCsv;

class CustomerImportController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        return Inertia::render('Bookings/Customers/Import', [
            'result' => $request->session()->get('import_result'),
            'urls' => [
                'index' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'store' => route('bookings.customers.import.store', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        ImportCustomersRequest $request,
        ImportCustomersCsv $import,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $result = $import->handle($team, $request->user(), $request->file('file'));

        Inertia::flash('toast', [
            'type' => $result['errors'] === [] ? 'success' : 'warning',
            'message' => "Importación terminada: {$result['created']} creados, {$result['skipped']} omitidos.",
        ]);

        return to_route('bookings.customers.import', ['current_team' => $team->slug])
            ->with('import_result', $result);
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
