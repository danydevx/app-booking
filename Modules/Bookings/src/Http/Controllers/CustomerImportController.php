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
use Modules\Listings\Models\Listing;

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

        $activeListing = $this->resolveListing($request, $team);

        return Inertia::render('Bookings/Customers/Import', [
            'result' => $request->session()->get('import_result'),
            'activeListing' => $activeListing,
            'management' => $activeListing ? $this->managementUrls($team, $activeListing) : null,
            'urls' => [
                'index' => $activeListing
                    ? route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $activeListing->slug])
                    : route('bookings.customers.index', ['current_team' => $team->slug]),
                'store' => route('bookings.customers.import.store', [
                    'current_team' => $team->slug,
                    ...($activeListing ? ['listing' => $activeListing->slug] : []),
                ]),
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

        $activeListing = $this->resolveListing($request, $team);

        $result = $import->handle(
            $team,
            $request->user(),
            $request->file('file'),
            $activeListing,
        );

        Inertia::flash('toast', [
            'type' => $result['errors'] === [] ? 'success' : 'warning',
            'message' => "Importación terminada: {$result['created']} creados, {$result['skipped']} omitidos.",
        ]);

        return to_route('bookings.customers.import', [
            'current_team' => $team->slug,
            ...($activeListing ? ['listing' => $activeListing->slug] : []),
        ])->with('import_result', $result);
    }

    private function resolveListing(Request $request, Team $team): ?Listing
    {
        $slug = trim($request->string('listing')->toString());

        if ($slug === '') {
            return null;
        }

        $listing = Listing::query()
            ->where('team_id', $team->id)
            ->where('slug', $slug)
            ->first();

        abort_if($listing === null, 404);

        return $listing;
    }

    /**
     * @return array<string, string>
     */
    private function managementUrls(Team $team, Listing $listing): array
    {
        return [
            'manage' => route('listings.manage', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            'edit' => route('listings.edit', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            'bookings' => route('bookings.listing', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            'customers' => route('bookings.listing.customers', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            'availability' => route('bookings.availability.edit', ['current_team' => $team->slug, 'listing' => $listing->slug]),
            'listings' => route('listings.index', ['current_team' => $team->slug]),
        ];
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
