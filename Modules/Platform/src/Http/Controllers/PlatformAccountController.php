<?php

namespace Modules\Platform\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
use Modules\Platform\Support\ActivityPresenter;
use Modules\Platform\Support\StripeDashboard;
use Spatie\Activitylog\Models\Activity;

class PlatformAccountController extends Controller
{
    public function index(
        PlatformSearchRequest $request,
        Entitlements $entitlements,
    ): Response {
        $search = trim((string) $request->validated('q', ''));

        $accounts = Team::query()
            ->with([
                'subscriptions',
                'members' => fn ($query) => $query->wherePivot('role', TeamRole::Owner->value),
            ])
            ->withCount('members')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('stripe_id', 'like', '%'.$search.'%')
                        ->orWhereHas('members', fn ($member) => $member
                            ->where('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $teams = $accounts->getCollection();
        $listingCounts = $this->listingCounts($teams);
        $stripeStatuses = Subscription::query()
            ->whereIn('team_id', $teams->pluck('id'))
            ->where('type', 'default')
            ->pluck('stripe_status', 'team_id')
            ->all();

        $accounts->setCollection($teams->map(function (Team $team) use ($entitlements, $listingCounts, $stripeStatuses): array {
            $plan = $entitlements->planFor($team);

            return [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner_name' => $this->owner($team)?->name,
                'owner_email' => $this->owner($team)?->email,
                'plan' => $plan->name,
                'plan_slug' => $plan->slug,
                'status' => $entitlements->statusFor($team),
                'stripe_status' => $stripeStatuses[$team->id] ?? null,
                'listings_count' => (int) ($listingCounts[$team->id] ?? 0),
                'members_count' => (int) $team->members_count,
                'is_suspended' => $team->isSuspended(),
                'suspended_at' => $team->suspended_at?->toDateString(),
                'created_at' => $team->created_at?->toDateString(),
            ];
        }));

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
        $team = $this->findAccount($account);

        $team->load(['subscriptions', 'members', 'invitations']);

        $subscription = Subscription::query()
            ->where('team_id', $team->id)
            ->where('type', 'default')
            ->first();
        $plan = $entitlements->planFor($team);
        $listings = Listing::query()
            ->where('team_id', $team->id)
            ->with('coverImage')
            ->latest()
            ->get();

        $activities = Activity::query()
            ->with('causer')
            ->where('log_name', 'moderation')
            ->where('subject_type', Team::class)
            ->where('subject_id', $team->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity): array => ActivityPresenter::present($activity))
            ->all();

        return Inertia::render('Platform/Accounts/Show', [
            'account' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'timezone' => $team->timezone,
                'created_at' => $team->created_at?->toIso8601String(),
                'is_suspended' => $team->isSuspended(),
                'suspended_at' => $team->suspended_at?->toIso8601String(),
                'suspension_reason' => $team->suspension_reason,
                'stripe_id' => $team->stripe_id,
                'stripe_dashboard_url' => StripeDashboard::customerUrl($team->stripe_id),
            ],
            'owner' => $this->owner($team),
            'members' => $team->members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role->value,
                'role_label' => $member->pivot->role->label(),
                'joined_at' => $member->pivot->created_at?->toDateString(),
            ])->all(),
            'listings' => $listings->map(fn (Listing $listing): array => [
                'id' => $listing->id,
                'name' => $listing->name,
                'slug' => $listing->slug,
                'status' => $listing->status->value,
                'status_label' => $listing->status->label(),
                'city' => $listing->city,
                'is_suspended' => $listing->isSuspendedByPlatform(),
                'public_url' => $listing->isPubliclyVisible()
                    ? route('public.listings.show', ['listing' => $listing->slug])
                    : null,
                'platform_url' => route('platform.listings.show', ['listing' => $listing->slug]),
            ])->all(),
            'billing' => [
                'plan' => $plan->name,
                'plan_slug' => $plan->slug,
                'status' => $entitlements->statusFor($team),
                'stripe_status' => $subscription?->stripe_status,
                'ends_at' => $subscription?->ends_at?->toDateString(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toDateString(),
                'has_customer' => $team->stripe_id !== null,
                'listings_limit' => $plan->limit('listings'),
                'dashboard_url' => StripeDashboard::customerUrl($team->stripe_id),
            ],
            'activities' => $activities,
            'can' => [
                'suspend' => Gate::allows('platform.accounts.suspend'),
            ],
            'urls' => [
                'index' => route('platform.accounts.index'),
                'suspend' => route('platform.accounts.suspend', ['account' => $team->slug]),
                'restore' => route('platform.accounts.restore', ['account' => $team->slug]),
            ],
        ]);
    }

    public function suspend(
        ModerationReasonRequest $request,
        string $account,
        SuspendAccount $suspendAccount,
    ): RedirectResponse {
        $team = $this->findAccount($account);

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
        $team = $this->findAccount($account);

        $restoreAccount->handle($team, $request->user(), (string) $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Cuenta {$team->name} restaurada.",
        ]);

        return back();
    }

    private function findAccount(string $account): Team
    {
        return Team::query()->where('slug', $account)->firstOrFail();
    }

    private function owner(Team $team): ?User
    {
        return $team->members->first(
            fn (User $member): bool => $member->pivot->role === TeamRole::Owner,
        );
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, int>
     */
    private function listingCounts(Collection $teams): array
    {
        if ($teams->isEmpty()) {
            return [];
        }

        return Listing::query()
            ->whereIn('team_id', $teams->pluck('id'))
            ->selectRaw('team_id, count(*) as aggregate')
            ->groupBy('team_id')
            ->pluck('aggregate', 'team_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
