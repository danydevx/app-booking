<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\PlatformActivityFilterRequest;
use Modules\Platform\Support\ActivityPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformActivityController extends Controller
{
    public function __invoke(PlatformActivityFilterRequest $request): Response
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['q'] ?? ''));
        $actor = trim((string) ($filters['actor'] ?? ''));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->where('log_name', 'moderation')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('description', 'like', '%'.$search.'%')
                        ->orWhere('subject_type', 'like', '%'.$search.'%');
                });
            })
            ->when($actor !== '', function ($query) use ($actor): void {
                $query->whereHasMorph(
                    'causer',
                    [User::class],
                    fn ($causer) => $causer
                        ->where('name', 'like', '%'.$actor.'%')
                        ->orWhere('email', 'like', '%'.$actor.'%'),
                );
            })
            ->when($from !== null, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $activities->through(
            fn (Activity $activity): array => ActivityPresenter::present($activity),
        );

        return Inertia::render('Platform/Activity/Index', [
            'activities' => $activities,
            'filters' => [
                'q' => $search,
                'actor' => $actor,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('platform.activity.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }
}
