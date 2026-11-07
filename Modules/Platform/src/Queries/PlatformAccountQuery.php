<?php

namespace Modules\Platform\Queries;

use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;
use Modules\Listings\Models\Listing;

final class PlatformAccountQuery
{
    /**
     * @return LengthAwarePaginator<int, Team>
     */
    public function paginate(string $search): LengthAwarePaginator
    {
        return Team::query()
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
    }

    public function find(string $slug): Team
    {
        return Team::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, int>
     */
    public function listingCounts(Collection $teams): array
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

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, string|null>
     */
    public function stripeStatuses(Collection $teams): array
    {
        return Subscription::query()
            ->whereIn('team_id', $teams->pluck('id'))
            ->where('type', 'default')
            ->pluck('stripe_status', 'team_id')
            ->all();
    }
}
