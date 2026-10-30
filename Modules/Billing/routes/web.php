<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\BillingController;

Route::prefix('{current_team}/billing')
    ->middleware(['web', 'auth', 'verified', EnsureTeamMembership::class])
    ->name('billing.')
    ->group(function (): void {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('/checkout', [BillingController::class, 'checkout'])->name('checkout');
        Route::post('/portal', [BillingController::class, 'portal'])->name('portal');
    });
