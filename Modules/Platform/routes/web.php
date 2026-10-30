<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\PlatformDashboardController;
use Modules\Platform\Support\PlatformPermission;

Route::middleware(['web', 'auth', 'verified', 'can:'.PlatformPermission::ACCESS])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/', PlatformDashboardController::class)->name('dashboard');
    });
