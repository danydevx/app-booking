<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Modules\Listings\Models\Listing;
use Modules\Platform\Enums\ReportStatus;
use Modules\Platform\Models\ListingReport;
use Modules\Platform\Support\ActivityPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $now = Carbon::now();

        $accounts = Team::query()->count();

        $paid = Subscription::query()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->where(fn ($query) => $query
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', $now))
            ->distinct()
            ->count('team_id');

        $canceled = Subscription::query()
            ->where(fn ($query) => $query
                ->where('stripe_status', 'canceled')
                ->orWhere(fn ($inner) => $inner
                    ->whereNotNull('ends_at')
                    ->where('ends_at', '<=', $now)))
            ->distinct()
            ->count('team_id');

        $free = Team::query()->whereDoesntHave('subscriptions')->count();

        $recentSubscriptions = Subscription::query()
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $teamNames = Team::query()
            ->whereIn('id', $recentSubscriptions->pluck('team_id'))
            ->pluck('name', 'id')
            ->all();

        return Inertia::render('Platform/Dashboard', [
            'stats' => [
                'accounts' => $accounts,
                'users' => User::query()->count(),
                'paid' => $paid,
                'free' => $free,
                'canceled' => $canceled,
                'listings' => Listing::query()->count(),
                'published_listings' => Listing::query()->published()->count(),
                'pending_reports' => ListingReport::query()
                    ->whereIn('status', [ReportStatus::Open, ReportStatus::Reviewing])
                    ->count(),
            ],
            'recentSignups' => User::query()
                ->latest()
                ->limit(6)
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toIso8601String(),
                ])
                ->all(),
            'recentSubscriptions' => $recentSubscriptions
                ->map(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'team_name' => $teamNames[$subscription->team_id] ?? null,
                    'stripe_status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at?->toDateString(),
                    'updated_at' => $subscription->updated_at?->toIso8601String(),
                ])
                ->all(),
            'recentActivity' => Activity::query()
                ->with(['causer', 'subject'])
                ->where('log_name', 'moderation')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Activity $activity): array => ActivityPresenter::present($activity))
                ->all(),
            'urls' => [
                'accounts' => route('platform.accounts.index'),
                'listings' => route('platform.listings.index'),
                'subscriptions' => route('platform.subscriptions.index'),
                'reports' => route('platform.reports.index'),
            ],
        ]);
    }
}
