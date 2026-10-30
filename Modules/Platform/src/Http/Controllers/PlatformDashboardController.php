<?php

namespace Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Platform/Dashboard', [
            'stats' => [
                'accounts' => Team::query()->count(),
                'users' => User::query()->count(),
            ],
        ]);
    }
}
