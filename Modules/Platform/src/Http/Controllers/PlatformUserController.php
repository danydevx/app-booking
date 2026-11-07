<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Actions\RestoreUser;
use Modules\Platform\Actions\SuspendUser;
use Modules\Platform\Http\Requests\ModerationReasonRequest;
use Modules\Platform\Http\Requests\PlatformSearchRequest;
use Modules\Platform\Queries\PlatformUserQuery;
use Modules\Platform\Support\PlatformAccess;
use Modules\Platform\Support\PlatformPermission;
use Modules\Platform\Support\PlatformUserPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformUserController extends Controller
{
    public function index(
        PlatformSearchRequest $request,
        PlatformUserQuery $users,
    ): Response {
        $search = trim((string) $request->validated('q', ''));

        $accounts = $users->paginate($search);
        $accounts->through(
            fn (User $user): array => PlatformUserPresenter::indexRow($user),
        );

        return Inertia::render('Platform/Users/Index', [
            'users' => $accounts,
            'filters' => ['q' => $search],
            'urls' => [
                'index' => route('platform.users.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->load(['currentTeam', 'teams']);

        $activities = Activity::query()
            ->with('causer')
            ->where('log_name', 'moderation')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $canSuspend = Gate::allows(PlatformPermission::USERS_SUSPEND)
            && ! $request->user()->is($user)
            && ! PlatformAccess::isSuperadmin($user);

        return Inertia::render('Platform/Users/Show', PlatformUserPresenter::detail(
            $user,
            $activities,
            $canSuspend,
        ));
    }

    public function suspend(
        ModerationReasonRequest $request,
        User $user,
        SuspendUser $suspendUser,
    ): RedirectResponse {
        $suspendUser->handle($user, $request->user(), (string) $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Usuario {$user->email} suspendido.",
        ]);

        return back();
    }

    public function restore(
        ModerationReasonRequest $request,
        User $user,
        RestoreUser $restoreUser,
    ): RedirectResponse {
        $restoreUser->handle($user, $request->user(), (string) $request->validated('reason'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Usuario {$user->email} restaurado.",
        ]);

        return back();
    }
}
