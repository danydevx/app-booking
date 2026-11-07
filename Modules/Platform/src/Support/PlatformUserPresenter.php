<?php

namespace Modules\Platform\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

final class PlatformUserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function indexRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'is_superadmin' => PlatformAccess::isSuperadmin($user),
            'is_suspended' => $user->isSuspended(),
            'teams_count' => (int) $user->teams_count,
            'owned_teams_count' => (int) $user->owned_teams_count,
            'current_team' => $user->currentTeam
                ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                    'slug' => $user->currentTeam->slug,
                ]
                : null,
            'created_at' => $user->created_at?->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, mixed>
     */
    public static function detail(User $user, Collection $activities, bool $canSuspend): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->email_verified_at !== null,
                'is_superadmin' => PlatformAccess::isSuperadmin($user),
                'is_suspended' => $user->isSuspended(),
                'suspended_at' => $user->suspended_at?->toIso8601String(),
                'suspension_reason' => $user->suspension_reason,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'current_team' => $user->currentTeam
                ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name,
                    'slug' => $user->currentTeam->slug,
                ]
                : null,
            'teams' => $user->teams
                ->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'role' => $team->pivot->role->value,
                    'role_label' => $team->pivot->role->label(),
                    'is_current' => $team->id === $user->current_team_id,
                    'platform_url' => route('platform.accounts.show', ['account' => $team->slug]),
                ])
                ->values()
                ->all(),
            'activities' => $activities
                ->map(fn (Activity $activity): array => ActivityPresenter::present($activity))
                ->all(),
            'can' => [
                'suspend' => $canSuspend,
            ],
            'urls' => [
                'index' => route('platform.users.index'),
                'suspend' => route('platform.users.suspend', ['user' => $user->id]),
                'restore' => route('platform.users.restore', ['user' => $user->id]),
            ],
        ];
    }
}
