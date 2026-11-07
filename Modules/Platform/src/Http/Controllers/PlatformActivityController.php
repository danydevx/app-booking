<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Support\ActivityPresenter;
use Spatie\Activitylog\Models\Activity;

class PlatformActivityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim($request->string('q')->toString());

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->where('log_name', 'moderation')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('description', 'like', '%'.$search.'%')
                        ->orWhere('subject_type', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $activities->through(
            fn (Activity $activity): array => ActivityPresenter::present($activity),
        );

        return Inertia::render('Platform/Activity/Index', [
            'activities' => $activities,
            'filters' => ['q' => $search],
            'urls' => [
                'index' => route('platform.activity.index'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }
}
