<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Modules\Listings\Models\Listing;
use Modules\Platform\Actions\RestoreAccount;
use Modules\Platform\Actions\SuspendAccount;
use Modules\Platform\Http\Requests\ModerationReasonRequest;
use Modules\Platform\Http\Requests\PlatformSearchRequest;
use Modules\Platform\Queries\PlatformAccountQuery;
use Modules\Platform\Support\PlatformAccountPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformAccountController extends Controller
{
    public function __construct(
        private readonly PlatformAccountQuery $accounts,
    ) {}

    public function index(
        PlatformSearchRequest $request,
        Entitlements $entitlements,
    ): Response {
        $search = trim((string) $request->validated('q', ''));

        $accounts = $this->accounts->paginate($search);
        $teams = $accounts->getCollection();
        $listingCounts = $this->accounts->listingCounts($teams);
        $stripeStatuses = $this->accounts->stripeStatuses($teams);

        $accounts->setCollection(
            $teams->map(fn (Team $team): array => PlatformAccountPresenter::indexRow(
                $team,
                $entitlements,
                $listingCounts,
                $stripeStatuses,
            )),
        );

        return Inertia::render('Platform/Accounts/Index', [
            'accounts' => $accounts,
            'filters' => ['q' => $search],
            'urls' => [
                'index' => route('platform.accounts.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function show(string $account, Entitlements $entitlements): Response
    {
        $team = $this->accounts->find($account);
        $team->load(['subscriptions', 'members', 'invitations']);

        $subscription = Subscription::query()
            ->where('team_id', $team->id)
            ->where('type', 'default')
            ->first();

        $listings = Listing::query()
            ->where('team_id', $team->id)
            ->with(['coverImage', 'team'])
            ->latest()
            ->get();

        $activities = Activity::query()
            ->with('causer')
            ->where('log_name', 'moderation')
            ->where('subject_type', Team::class)
            ->where('subject_id', $team->id)
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Platform/Accounts/Show', PlatformAccountPresenter::detail(
            $team,
            $entitlements,
            $subscription,
            $listings,
            $activities,
            Gate::allows('platform.accounts.suspend'),
        ));
    }

    public function suspend(
        ModerationReasonRequest $request,
        string $account,
        SuspendAccount $suspendAccount,
    ): RedirectResponse {
        $team = $this->accounts->find($account);

        $suspendAccount->handle($team, $request->user(), (string) $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Cuenta {$team->name} suspendida.",
        ]);

        return back();
    }

    public function restore(
        ModerationReasonRequest $request,
        string $account,
        RestoreAccount $restoreAccount,
    ): RedirectResponse {
        $team = $this->accounts->find($account);

        $restoreAccount->handle($team, $request->user(), (string) $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Cuenta {$team->name} restaurada.",
        ]);

        return back();
    }
}
