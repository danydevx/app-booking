<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\PlatformAccountController;
use Modules\Platform\Http\Controllers\PlatformActivityController;
use Modules\Platform\Http\Controllers\PlatformBrandingController;
use Modules\Platform\Http\Controllers\PlatformDashboardController;
use Modules\Platform\Http\Controllers\PlatformListingController;
use Modules\Platform\Http\Controllers\PlatformPlanController;
use Modules\Platform\Http\Controllers\PlatformReportController;
use Modules\Platform\Http\Controllers\PlatformSubscriptionController;
use Modules\Platform\Http\Controllers\PlatformSystemController;
use Modules\Platform\Http\Controllers\PlatformUserController;
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
    ->prefix('app/platform')
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

        Route::get('users', [PlatformUserController::class, 'index'])
            ->middleware('can:'.PlatformPermission::USERS_VIEW)
            ->name('users.index');
        Route::get('users/{user}', [PlatformUserController::class, 'show'])
            ->middleware('can:'.PlatformPermission::USERS_VIEW)
            ->name('users.show');
        Route::post('users/{user}/suspend', [PlatformUserController::class, 'suspend'])
            ->middleware('can:'.PlatformPermission::USERS_SUSPEND)
            ->name('users.suspend');
        Route::post('users/{user}/restore', [PlatformUserController::class, 'restore'])
            ->middleware('can:'.PlatformPermission::USERS_SUSPEND)
            ->name('users.restore');
        Route::post('users/{user}/resend-verification', [PlatformUserController::class, 'resendVerification'])
            ->middleware(['can:'.PlatformPermission::USERS_NOTIFY, 'throttle:10,1'])
            ->name('users.resend-verification');
        Route::post('users/{user}/send-password-reset', [PlatformUserController::class, 'sendPasswordReset'])
            ->middleware(['can:'.PlatformPermission::USERS_NOTIFY, 'throttle:10,1'])
            ->name('users.send-password-reset');
        Route::post('users/{user}/grant-superadmin', [PlatformUserController::class, 'grantSuperadmin'])
            ->middleware('can:'.PlatformPermission::USERS_MANAGE_ROLES)
            ->name('users.grant-superadmin');
        Route::post('users/{user}/revoke-superadmin', [PlatformUserController::class, 'revokeSuperadmin'])
            ->middleware('can:'.PlatformPermission::USERS_MANAGE_ROLES)
            ->name('users.revoke-superadmin');

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

        Route::get('plans', [PlatformPlanController::class, 'index'])
            ->middleware('can:'.PlatformPermission::PLANS_VIEW)
            ->name('plans.index');
        Route::post('plans', [PlatformPlanController::class, 'store'])
            ->middleware('can:'.PlatformPermission::PLANS_MANAGE)
            ->name('plans.store');
        Route::patch('plans/{plan}', [PlatformPlanController::class, 'update'])
            ->middleware('can:'.PlatformPermission::PLANS_MANAGE)
            ->name('plans.update');

        Route::get('activity/export', [PlatformActivityController::class, 'export'])
            ->middleware('can:'.PlatformPermission::ACTIVITY_VIEW)
            ->name('activity.export');

        Route::get('activity', PlatformActivityController::class)
            ->middleware('can:'.PlatformPermission::ACTIVITY_VIEW)
            ->name('activity.index');

        Route::get('system', PlatformSystemController::class)
            ->middleware('can:'.PlatformPermission::SYSTEM_VIEW)
            ->name('system.index');
        Route::post('system/failed-jobs/{uuid}/retry', [PlatformSystemController::class, 'retryFailedJob'])
            ->middleware('can:'.PlatformPermission::SYSTEM_MANAGE)
            ->name('system.failed-jobs.retry');
        Route::delete('system/failed-jobs/{uuid}', [PlatformSystemController::class, 'forgetFailedJob'])
            ->middleware('can:'.PlatformPermission::SYSTEM_MANAGE)
            ->name('system.failed-jobs.destroy');

        Route::get('reports', [PlatformReportController::class, 'index'])
            ->middleware('can:'.PlatformPermission::REPORTS_VIEW)
            ->name('reports.index');
        Route::patch('reports/{report}', [PlatformReportController::class, 'update'])
            ->middleware('can:'.PlatformPermission::REPORTS_MANAGE)
            ->name('reports.update');

        Route::get('branding', [PlatformBrandingController::class, 'edit'])
            ->middleware('can:'.PlatformPermission::BRANDING_MANAGE)
            ->name('branding.edit');
        Route::patch('branding', [PlatformBrandingController::class, 'update'])
            ->middleware('can:'.PlatformPermission::BRANDING_MANAGE)
            ->name('branding.update');
    });
