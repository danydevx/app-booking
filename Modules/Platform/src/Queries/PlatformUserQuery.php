<?php

namespace Modules\Platform\Queries;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class PlatformUserQuery
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(string $search): LengthAwarePaginator
    {
        return User::query()
            ->with(['currentTeam'])
            ->withCount(['teams', 'ownedTeams'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('teams', fn ($team) => $team
                            ->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }
}
