<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\PlatformActivityFilterRequest;
use Modules\Platform\Queries\PlatformActivityQuery;
use Modules\Platform\Support\ActivityPresenter;
use Modules\Platform\Support\PlatformActivityCsvExport;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformActivityController extends Controller
{
    public function __construct(
        private readonly PlatformActivityQuery $query,
    ) {}

    public function __invoke(PlatformActivityFilterRequest $request): Response
    {
        $filters = $request->validated();

        $activities = $this->query->builder($filters)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $activities->through(
            fn (Activity $activity): array => ActivityPresenter::present($activity),
        );

        return Inertia::render('Platform/Activity/Index', [
            'activities' => $activities,
            'filters' => [
                'q' => trim((string) ($filters['q'] ?? '')),
                'actor' => trim((string) ($filters['actor'] ?? '')),
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
            ],
            'urls' => [
                'index' => route('platform.activity.index'),
                'export' => route('platform.activity.export'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function export(
        PlatformActivityFilterRequest $request,
        PlatformActivityCsvExport $export,
    ): StreamedResponse {
        return $export->stream($request->validated());
    }
}
