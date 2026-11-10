<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\CreateManualBooking;
use Modules\Bookings\Actions\RecordCustomerActivity;
use Modules\Bookings\Enums\CustomerActivityType;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Http\Requests\StoreCustomerQuoteRequest;
use Modules\Bookings\Http\Requests\UpdateCustomerQuoteRequest;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Notifications\CustomerQuoteSent;
use Modules\Bookings\Support\CustomerQuotePresenter;
use Modules\Listings\Models\Listing;

class CustomerQuoteController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly RecordCustomerActivity $activityLogger,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [CustomerQuote::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();
        $listingSlug = $request->string('listing')->toString();

        $quotes = CustomerQuote::query()
            ->with(['customer', 'listing'])
            ->where('team_id', $team->id)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($listingSlug !== '', fn ($query) => $query->whereHas(
                'listing',
                fn ($listingQuery) => $listingQuery->where('slug', $listingSlug),
            ))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('customer', function ($customerQuery) use ($search): void {
                    $customerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) config('bookings.per_page', 15))
            ->withQueryString();

        $quotes->through(
            fn (CustomerQuote $quote): array => CustomerQuotePresenter::present($quote, $team->slug),
        );

        return Inertia::render('Bookings/Quotes/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'quotes' => $quotes,
            'statuses' => QuoteStatus::options(),
            'filters' => [
                'status' => $status,
                'search' => $search,
                'listing' => $listingSlug,
            ],
            'listings' => Listing::query()
                ->where('team_id', $team->id)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Listing $listing): array => [
                    'id' => $listing->id,
                    'name' => $listing->name,
                    'slug' => $listing->slug,
                ])
                ->all(),
            'urls' => [
                'index' => route('bookings.quotes.index', ['current_team' => $team->slug]),
                'bookings' => route('bookings.index', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
                'billing' => route('billing.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        StoreCustomerQuoteRequest $request,
        string $currentTeam,
        Customer $customer,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedCustomer($customer, $team->id);

        Gate::authorize('create', [CustomerQuote::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $validated = $request->validated();

        $quote = $this->fillQuote(new CustomerQuote, $validated);
        $quote->team_id = $team->id;
        $quote->customer_id = $customer->id;
        $quote->listing_id = (int) $validated['listing_id'];
        $quote->created_by = $request->user()->id;
        $quote->status = QuoteStatus::Draft;
        $quote->currency = (string) config('bookings.deposit_currency', 'MXN');
        $quote->save();

        $this->activityLogger->handle(
            $customer,
            CustomerActivityType::System,
            "Cotización creada para {$quote->listing->name}.",
            $request->user(),
            null,
            ['customer_quote_id' => $quote->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización creada.']);

        return to_route('bookings.quotes.show', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]);
    }

    public function show(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
    ): Response|RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedQuote($quote, $team->id);

        Gate::authorize('view', $quote);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $quote->load(['customer', 'listing', 'convertedInquiry']);

        return Inertia::render('Bookings/Quotes/Show', [
            'quote' => CustomerQuotePresenter::present($quote, $team->slug),
            'statuses' => QuoteStatus::options(),
            'urls' => [
                'index' => route('bookings.quotes.index', ['current_team' => $team->slug]),
                'customer' => route('bookings.customers.show', [
                    'current_team' => $team->slug,
                    'customer' => $quote->customer_id,
                ]),
                'booking' => $quote->converted_inquiry_id
                    ? route('bookings.show', [
                        'current_team' => $team->slug,
                        'inquiry' => $quote->converted_inquiry_id,
                    ])
                    : null,
            ],
        ]);
    }

    public function update(
        UpdateCustomerQuoteRequest $request,
        string $currentTeam,
        CustomerQuote $quote,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedQuote($quote, $team->id);

        Gate::authorize('update', $quote);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages([
                'quote' => 'Esta cotización ya no se puede editar.',
            ]);
        }

        $validated = $request->validated();
        $quote = $this->fillQuote($quote, $validated);
        $quote->listing_id = (int) $validated['listing_id'];
        $quote->save();

        $this->activityLogger->handle(
            $quote->customer,
            CustomerActivityType::System,
            "Cotización actualizada para {$quote->listing->name}.",
            $request->user(),
            null,
            ['customer_quote_id' => $quote->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización actualizada.']);

        return back();
    }

    public function send(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedQuote($quote, $team->id);

        Gate::authorize('send', $quote);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if (! in_array($quote->status, [QuoteStatus::Draft, QuoteStatus::Sent], true)) {
            throw ValidationException::withMessages([
                'quote' => 'Esta cotización ya no se puede enviar.',
            ]);
        }

        $quote->forceFill([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ])->save();

        $quote->load(['customer', 'listing', 'team']);

        Notification::route('mail', $quote->customer->email)
            ->notify(new CustomerQuoteSent($quote));

        $this->activityLogger->handle(
            $quote->customer,
            CustomerActivityType::Email,
            "Cotización enviada para {$quote->listing->name}.",
            $request->user(),
            null,
            ['customer_quote_id' => $quote->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización enviada al cliente.']);

        return back();
    }

    public function convert(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
        CreateManualBooking $createBooking,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwnedQuote($quote, $team->id);

        Gate::authorize('convert', $quote);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        if ($quote->status !== QuoteStatus::Accepted || $quote->converted_inquiry_id !== null) {
            throw ValidationException::withMessages([
                'quote' => 'Solo una cotización aceptada y sin reserva se puede convertir.',
            ]);
        }

        $quote->load(['customer', 'listing']);

        $inquiry = $createBooking->handle(
            $quote->listing,
            $request->user(),
            [
                'name' => $quote->customer->name,
                'email' => $quote->customer->email,
                'phone' => $quote->customer->phone ?? $quote->customer->whatsapp_phone ?? '',
                'whatsapp_phone' => $quote->customer->whatsapp_phone,
                'start_date' => $quote->start_date->toDateString(),
                'end_date' => $quote->end_date->toDateString(),
                'event_type' => $quote->event_type,
                'guests' => $quote->guests,
                'message' => $quote->notes,
            ],
            true,
        );

        $quote->forceFill(['converted_inquiry_id' => $inquiry->id])->save();

        $this->activityLogger->handle(
            $quote->customer,
            CustomerActivityType::System,
            "Cotización convertida en reserva para {$quote->listing->name}.",
            $request->user(),
            null,
            [
                'customer_quote_id' => $quote->id,
                'booking_inquiry_id' => $inquiry->id,
            ],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cotización convertida en reserva.',
        ]);

        return to_route('bookings.show', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillQuote(CustomerQuote $quote, array $data): CustomerQuote
    {
        $quote->fill([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'event_type' => $data['event_type'] ?? null,
            'guests' => $data['guests'] ?? null,
            'subtotal_cents' => (int) round(((float) $data['subtotal']) * 100),
            'deposit_cents' => (int) round(((float) ($data['deposit'] ?? 0)) * 100),
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return $quote;
    }

    private function ensureOwnedCustomer(Customer $customer, int $teamId): void
    {
        abort_unless($customer->team_id === $teamId, 404);
    }

    private function ensureOwnedQuote(CustomerQuote $quote, int $teamId): void
    {
        abort_unless($quote->team_id === $teamId, 404);
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
