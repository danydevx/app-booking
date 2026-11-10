<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\ContractStatus;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Http\Requests\StoreBookingContractRequest;
use Modules\Bookings\Http\Requests\UpdateBookingContractRequest;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;

class BookingContractController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function store(
        StoreBookingContractRequest $request,
        string $currentTeam,
        BookingInquiry $inquiry,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedInquiry($inquiry, $team->id);

        Gate::authorize('create', [BookingContract::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if (BookingContract::query()->where('booking_inquiry_id', $inquiry->id)->exists()) {
            throw ValidationException::withMessages([
                'contract' => 'Esta reserva ya tiene un contrato.',
            ]);
        }

        $contract = new BookingContract($request->validated());
        $contract->team_id = $team->id;
        $contract->booking_inquiry_id = $inquiry->id;
        $contract->created_by = $request->user()->id;
        $contract->status = ContractStatus::Draft;
        $contract->save();

        $this->log($contract, 'Contrato creado.', $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato creado.']);

        return back();
    }

    public function update(
        UpdateBookingContractRequest $request,
        string $currentTeam,
        BookingContract $contract,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedContract($contract, $team->id);

        Gate::authorize('update', $contract);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if (! $contract->isEditable()) {
            throw ValidationException::withMessages([
                'contract' => 'Un contrato aceptado ya no se puede editar.',
            ]);
        }

        $contract->update($request->validated());

        $this->log($contract, 'Contrato actualizado.', $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato actualizado.']);

        return back();
    }

    public function publish(
        Request $request,
        string $currentTeam,
        BookingContract $contract,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedContract($contract, $team->id);

        Gate::authorize('publish', $contract);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if (! $contract->isEditable()) {
            throw ValidationException::withMessages([
                'contract' => 'Un contrato aceptado ya no se puede publicar de nuevo.',
            ]);
        }

        $contract->forceFill([
            'status' => ContractStatus::Sent,
            'sent_at' => now(),
        ])->save();

        $this->log($contract, 'Contrato publicado en el portal del cliente.', $request);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Contrato disponible en el portal del cliente.',
        ]);

        return back();
    }

    private function log(BookingContract $contract, string $body, Request $request): void
    {
        $customer = $contract->bookingInquiry->customer;

        if ($customer === null) {
            return;
        }

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            $body,
            $request->user(),
            null,
            ['booking_contract_id' => $contract->id],
        );
    }

    private function ensureOwnedInquiry(BookingInquiry $inquiry, int $teamId): void
    {
        abort_unless($inquiry->team_id === $teamId, 404);
    }

    private function ensureOwnedContract(BookingContract $contract, int $teamId): void
    {
        abort_unless($contract->team_id === $teamId, 404);
    }

    private function upgradeRedirect(Team $team): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Los contratos están disponibles en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }
}
