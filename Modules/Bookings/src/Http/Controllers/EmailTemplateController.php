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
use Modules\Bookings\Http\Requests\StoreEmailTemplateRequest;
use Modules\Bookings\Http\Requests\UpdateEmailTemplateRequest;
use Modules\Bookings\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
    use ResolvesCurrentTeam;

    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $team = $this->currentTeam($request);

        Gate::authorize('viewAny', [EmailTemplate::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        return Inertia::render('Bookings/Templates/Index', [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'templates' => EmailTemplate::query()
                ->where('team_id', $team->id)
                ->orderBy('name')
                ->get()
                ->map(fn (EmailTemplate $template): array => $this->present($template))
                ->all(),
            'urls' => [
                'index' => route('bookings.templates.index', ['current_team' => $team->slug]),
                'store' => route('bookings.templates.store', ['current_team' => $team->slug]),
                'customers' => route('bookings.customers.index', ['current_team' => $team->slug]),
            ],
        ]);
    }

    public function store(
        StoreEmailTemplateRequest $request,
    ): RedirectResponse {
        $team = $this->currentTeam($request);

        Gate::authorize('create', [EmailTemplate::class, $team]);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $template = new EmailTemplate($request->validated());
        $template->team_id = $team->id;
        $template->created_by = $request->user()->id;
        $template->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla creada.']);

        return back();
    }

    public function update(
        UpdateEmailTemplateRequest $request,
        string $currentTeam,
        EmailTemplate $template,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($template, $team->id);

        Gate::authorize('update', $template);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $template->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla actualizada.']);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        EmailTemplate $template,
    ): RedirectResponse {
        $team = $this->currentTeam($request);
        $this->ensureOwned($template, $team->id);

        Gate::authorize('delete', $template);

        if (! $this->entitlements->allows($team, 'customers')) {
            return $this->upgradeRedirect($team);
        }

        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plantilla eliminada.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'subject' => $template->subject,
            'body' => $template->body,
            'update_url' => route('bookings.templates.update', [
                'current_team' => $template->team->slug,
                'template' => $template->id,
            ]),
            'delete_url' => route('bookings.templates.destroy', [
                'current_team' => $template->team->slug,
                'template' => $template->id,
            ]),
        ];
    }

    private function ensureOwned(EmailTemplate $template, int $teamId): void
    {
        abort_unless($template->team_id === $teamId, 404);
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
