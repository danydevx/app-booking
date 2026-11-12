<?php

namespace Modules\Bookings\Http\Controllers;

use App\Concerns\ResolvesCurrentTeam;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Support\Entitlements;
use Modules\Bookings\Actions\CancelQuotation;
use Modules\Bookings\Actions\DuplicateQuotation;
use Modules\Bookings\Actions\IssueQuotation;
use Modules\Bookings\Actions\SaveQuotation;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Http\Requests\SaveQuotationRequest;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Support\CustomerQuotePresenter;
use Modules\Bookings\Support\QuotationBuilderData;
use Modules\Bookings\Support\QuotationPdfRenderer;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationBuilderController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly QuotationBuilderData $builderData,
        private readonly QuotationPdfRenderer $pdf,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        return Inertia::render('Bookings/Quotes/Builder', [
            ...$this->builderData->forTeam($team),
            'quote' => null,
            'statuses' => QuoteStatus::options(),
            'prefill' => [
                'listing_id' => $request->integer('listing') ?: null,
                'customer_id' => $request->integer('customer') ?: null,
                'inquiry_id' => $request->integer('inquiry') ?: null,
                'start_date' => $request->string('start_date')->toString() ?: null,
                'end_date' => $request->string('end_date')->toString() ?: null,
                'event_type' => $request->string('event_type')->toString() ?: null,
                'guests' => $request->integer('guests') ?: null,
            ],
            'urls' => $this->urls($team),
        ]);
    }

    public function store(
        SaveQuotationRequest $request,
        SaveQuotation $save,
        IssueQuotation $issue,
    ): SymfonyResponse {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $data = $request->validated();
        $quote = $save->handle($team, $request->user(), $data);
        $intent = (string) ($data['intent'] ?? 'draft');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Borrador de cotización guardado.']);

        if ($intent === 'issue') {
            $issue->handle($quote, $request->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Cotización emitida y PDF generado.',
            ]);

            return to_route('bookings.quotes.show', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]);
        }

        if ($intent === 'preview') {
            // Inertia::location fuerza una navegación completa para mostrar
            // el PDF en el navegador en lugar de intentar interpretarlo como
            // una respuesta Inertia.
            return Inertia::location(route('bookings.quotes.preview', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]));
        }

        return to_route('bookings.quotes.edit', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]);
    }

    public function edit(Request $request, string $currentTeam, CustomerQuote $quote): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        return Inertia::render('Bookings/Quotes/Builder', [
            ...$this->builderData->forTeam($team),
            'quote' => CustomerQuotePresenter::present($quote, $team->slug),
            'statuses' => QuoteStatus::options(),
            'prefill' => null,
            'urls' => [
                ...$this->urls($team),
                'update' => route('bookings.quotes.update', [
                    'current_team' => $team->slug,
                    'quote' => $quote->id,
                ]),
            ],
        ]);
    }

    public function update(
        SaveQuotationRequest $request,
        string $currentTeam,
        CustomerQuote $quote,
        SaveQuotation $save,
        IssueQuotation $issue,
    ): SymfonyResponse {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        if (! $quote->status->isEditable()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Solo los borradores se pueden editar. Duplica la cotización para hacer cambios.',
            ]);

            return back();
        }

        $data = $request->validated();
        $quote = $save->handle($team, $request->user(), $data, $quote);
        $intent = (string) ($data['intent'] ?? 'draft');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización actualizada.']);

        if ($intent === 'issue') {
            $issue->handle($quote, $request->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Cotización emitida y PDF generado.',
            ]);

            return to_route('bookings.quotes.show', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]);
        }

        if ($intent === 'preview') {
            // Inertia::location fuerza una navegación completa para mostrar
            // el PDF en el navegador en lugar de intentar interpretarlo como
            // una respuesta Inertia.
            return Inertia::location(route('bookings.quotes.preview', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]));
        }

        return to_route('bookings.quotes.edit', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]);
    }

    /**
     * Vista previa basada exactamente en el mismo renderizador que el PDF final.
     */
    public function preview(Request $request, string $currentTeam, CustomerQuote $quote): HttpResponse|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $disk = (string) config('bookings.quotes.pdf_disk', 'local');
        $binary = filled($quote->pdf_path) && Storage::disk($disk)->exists((string) $quote->pdf_path)
            ? (string) Storage::disk($disk)->get((string) $quote->pdf_path)
            : $this->pdf->stream($quote);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename($quote).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function pdf(Request $request, string $currentTeam, CustomerQuote $quote): HttpResponse|StreamedResponse|RedirectResponse
    {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $disk = (string) config('bookings.quotes.pdf_disk', 'local');

        if (filled($quote->pdf_path) && Storage::disk($disk)->exists((string) $quote->pdf_path)) {
            return Storage::disk($disk)->download(
                (string) $quote->pdf_path,
                $this->filename($quote),
                ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'],
            );
        }

        $binary = $this->pdf->stream($quote);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($quote).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function issue(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
        IssueQuotation $issue,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $issue->handle($quote, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cotización emitida y PDF generado.',
        ]);

        return to_route('bookings.quotes.show', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]);
    }

    public function duplicate(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
        DuplicateQuotation $duplicate,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $copy = $duplicate->handle($quote, $request->user());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cotización duplicada como borrador.',
        ]);

        return to_route('bookings.quotes.edit', [
            'current_team' => $team->slug,
            'quote' => $copy->id,
        ]);
    }

    public function cancel(
        Request $request,
        string $currentTeam,
        CustomerQuote $quote,
        CancelQuotation $cancel,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $blocked = $this->authorizeQuotations($request, $team, $quote);

        if ($blocked instanceof RedirectResponse) {
            return $blocked;
        }

        $cancel->handle($quote, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cotización cancelada.']);

        return back();
    }

    /**
     * Valida entitlement, propiedad y permiso de la cuenta.
     */
    private function authorizeQuotations(
        Request $request,
        Team $team,
        ?CustomerQuote $quote = null,
    ): ?RedirectResponse {
        if ($quote !== null) {
            abort_unless($quote->team_id === $team->id, 404);
            Gate::authorize('view', $quote);
        } else {
            Gate::authorize('create', [CustomerQuote::class, $team]);
        }

        if ($this->entitlements->allows($team, 'quotations')) {
            return null;
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => 'Las cotizaciones en PDF están disponibles en el plan Pro.',
        ]);

        return to_route('billing.index', ['current_team' => $team->slug]);
    }

    /**
     * @return array<string, string>
     */
    private function urls(Team $team): array
    {
        return [
            'index' => route('bookings.quotes.index', ['current_team' => $team->slug]),
            'store' => route('bookings.quotes.store', ['current_team' => $team->slug]),
            'bookings' => route('bookings.index', ['current_team' => $team->slug]),
            'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
            'billing' => route('billing.index', ['current_team' => $team->slug]),
        ];
    }

    private function filename(CustomerQuote $quote): string
    {
        $folio = $quote->folio ?? 'COT-'.str_pad((string) $quote->id, 6, '0', STR_PAD_LEFT);

        return $folio.'.pdf';
    }
}
