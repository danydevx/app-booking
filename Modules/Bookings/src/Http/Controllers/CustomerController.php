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
use Modules\Bookings\Actions\CreateCustomer;
use Modules\Bookings\Http\Requests\StoreCustomerRequest;
use Modules\Bookings\Http\Requests\UpdateCustomerRequest;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Support\BookingPresenter;
use Modules\Bookings\Support\CustomerCsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->where('team_id', $team->id)
            ->withCount('inquiries')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) config('bookings.per_page', 15))
            ->withQueryString();

        $customers->through(
            fn (Customer $customer): array => BookingPresenter::customerCard($customer, $team->slug),
        );

        return Inertia::render('Bookings/Customers/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'customers' => $customers,
            'filters' => ['search' => $search],
            'urls' => [
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'index' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'store' => route('bookings.customers.store', ['current_team' => $team->slug]),
                'export' => route('bookings.customers.export', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomer $createCustomer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer = $createCustomer->handle($team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return to_route('bookings.customers.show', [
            'current_team' => $team->slug,
            'customer' => $customer->id,
        ]);
    }

    public function export(
        Request $request,
        CustomerCsvExport $export,
    ): StreamedResponse|RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [Customer::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        return $export->stream($team);
    }

    public function show(Request $request, string $currentTeam, Customer $customer): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('view', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->load(['inquiries.listing', 'inquiries.deposits']);
        $customer->loadCount('inquiries');

        return Inertia::render('Bookings/Customers/Show', [
            'customer' => [
                ...BookingPresenter::customerCard($customer, $team->slug),
                'inquiries' => $customer->inquiries
                    ->map(fn (BookingInquiry $inquiry): array => BookingPresenter::inquiryCard($inquiry, $team->slug))
                    ->all(),
            ],
            'urls' => [
                'index' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'update' => route('bookings.customers.update', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]),
            ],
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        string $currentTeam,
        Customer $customer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($customer, $team->id);

        Gate::authorize('update', $customer);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado.']);

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
