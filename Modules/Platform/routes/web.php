<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\PlatformAccountController;
use Modules\Platform\Http\Controllers\PlatformDashboardController;
use Modules\Platform\Http\Controllers\PlatformListingController;
use Modules\Platform\Http\Controllers\PlatformReportController;
use Modules\Platform\Http\Controllers\PlatformSubscriptionController;
use Modules\Platform\Http\Controllers\PublicListingReportController;
use Modules\Platform\Support\PlatformPermission;

Route::middleware('web')->group(function (): void {
    Route::get('terrazas/{listing:slug}/reportar', [PublicListingReportController::class, 'create'])
        ->name('public.reports.create');
    Route::post('terrazas/{listing:slug}/reportar', [PublicListingReportController::class, 'store'])
        ->middleware('throttle:listing-reports')
        ->name('public.reports.store');
});

Route::middleware(['web', 'auth', 'verified', 'can:'.PlatformPermission::ACCESS])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/', PlatformDashboardController::class)
            ->middleware('can:'.PlatformPermission::ACCOUNTS_VIEW)
            ->name('dashboard');

        Route::get('accounts', [PlatformAccountController::class, 'index'])
            ->middleware('can:'.PlatformPermission::ACCOUNTS_VIEW)
            ->name('accounts.index');
        Route::get('accounts/{account}', [PlatformAccountController::class, 'show'])
            ->middleware('can:'.PlatformPermission::ACCOUNTS_VIEW)
            ->name('accounts.show');
        Route::post('accounts/{account}/suspend', [PlatformAccountController::class, 'suspend'])
            ->middleware('can:'.PlatformPermission::ACCOUNTS_SUSPEND)
            ->name('accounts.suspend');
        Route::post('accounts/{account}/restore', [PlatformAccountController::class, 'restore'])
            ->middleware('can:'.PlatformPermission::ACCOUNTS_SUSPEND)
            ->name('accounts.restore');

        Route::get('listings', [PlatformListingController::class, 'index'])
            ->middleware('can:'.PlatformPermission::LISTINGS_VIEW)
            ->name('listings.index');
        Route::get('listings/{listing}', [PlatformListingController::class, 'show'])
            ->middleware('can:'.PlatformPermission::LISTINGS_VIEW)
            ->name('listings.show');
        Route::post('listings/{listing}/suspend', [PlatformListingController::class, 'suspend'])
            ->middleware('can:'.PlatformPermission::LISTINGS_MODERATE)
            ->name('listings.suspend');
        Route::post('listings/{listing}/restore', [PlatformListingController::class, 'restore'])
            ->middleware('can:'.PlatformPermission::LISTINGS_MODERATE)
            ->name('listings.restore');

        Route::get('subscriptions', [PlatformSubscriptionController::class, 'index'])
            ->middleware('can:'.PlatformPermission::SUBSCRIPTIONS_VIEW)
            ->name('subscriptions.index');

        Route::get('reports', [PlatformReportController::class, 'index'])
            ->middleware('can:'.PlatformPermission::REPORTS_VIEW)
            ->name('reports.index');
        Route::patch('reports/{report}', [PlatformReportController::class, 'update'])
            ->middleware('can:'.PlatformPermission::REPORTS_MANAGE)
            ->name('reports.update');
    });
