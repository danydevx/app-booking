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
use Modules\Bookings\Actions\CreateCustomerTag;
use Modules\Bookings\Actions\SyncCustomerTags;
use Modules\Bookings\Http\Requests\StoreCustomerTagRequest;
use Modules\Bookings\Http\Requests\SyncCustomerTagsRequest;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerTag;

class CustomerTagController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function store(
        StoreCustomerTagRequest $request,
        CreateCustomerTag $createTag,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [CustomerTag::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $createTag->handle($team, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Etiqueta creada.',
        ]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        CustomerTag $tag,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        abort_unless($tag->team_id === $team->id, 404);

        Gate::authorize('delete', $tag);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $tag->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Etiqueta eliminada.',
        ]);

        return back();
    }

    public function sync(
        SyncCustomerTagsRequest $request,
        string $currentTeam,
        Customer $customer,
        SyncCustomerTags $syncTags,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        abort_unless($customer->team_id === $team->id, 404);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $syncTags->handle(
            $customer,
            $request->user(),
            $request->validated('tag_ids', []),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Etiquetas actualizadas.',
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
