<?php

namespace Modules\Platform\Http\Controllers;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Modules\Billing\Support\Entitlements;
use Modules\Platform\Http\Requests\PlatformSearchRequest;
use Modules\Platform\Support\StripeDashboard;

class PlatformSubscriptionController extends Controller
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
            ->whereHas('subscriptions')
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
        $subscriptions = Subscription::query()
            ->whereIn('team_id', $teams->pluck('id'))
            ->where('type', 'default')
            ->get()
            ->keyBy('team_id')
            ->all();

        $accounts->setCollection($teams->map(function (Team $team) use ($entitlements, $subscriptions): array {
            $subscription = $subscriptions[$team->id] ?? null;
            $plan = $entitlements->planFor($team);
            $owner = $team->members->first(
                fn (User $member): bool => $member->pivot->role === TeamRole::Owner,
            );

            return [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'owner_email' => $owner?->email,
                'plan' => $plan->name,
                'plan_slug' => $plan->slug,
                'status' => $entitlements->statusFor($team),
                'stripe_status' => $subscription?->stripe_status,
                'ends_at' => $subscription?->ends_at?->toDateString(),
                'trial_ends_at' => $subscription?->trial_ends_at?->toDateString(),
                'started_at' => $subscription?->created_at?->toDateString(),
                'stripe_dashboard_url' => StripeDashboard::customerUrl($team->stripe_id),
                'account_url' => route('platform.accounts.show', ['account' => $team->slug]),
            ];
        }));

        return Inertia::render('Platform/Subscriptions/Index', [
            'accounts' => $accounts,
            'filters' => ['q' => $search],
            'urls' => [
                'index' => route('platform.subscriptions.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }
}
