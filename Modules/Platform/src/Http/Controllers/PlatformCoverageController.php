<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Platform\Http\Requests\UpdateGeographicCoverageRequest;
use Modules\Platform\Support\GeographicCoverageService;

class PlatformCoverageController extends Controller
{
    public function edit(GeographicCoverageService $coverage): Response
    {
        return Inertia::render('Platform/Coverage/Index', [
            'states' => $coverage->adminStates(),
            'urls' => [
                'update' => route('platform.coverage.update'),
                'dashboard' => route('platform.dashboard'),
            ],
        ]);
    }

    public function update(
        UpdateGeographicCoverageRequest $request,
        GeographicCoverageService $coverage,
    ): RedirectResponse {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $coverage->updateCoverage((array) $request->validated('states'), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Cobertura geográfica actualizada.',
        ]);

        return back();
    }
}
