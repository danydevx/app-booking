<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OwnersController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('propietarios', OwnersController::class)->name('public.owners');

Route::prefix('app/{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::prefix('app')->middleware(['auth'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

$dashboardRedirect = function (Request $request) {
    $user = $request->user();
    $team = $user ? ($user->currentTeam ?? $user->personalTeam()) : null;

    return $team
        ? redirect()->route('dashboard', ['current_team' => $team->slug])
        : redirect()->route('home');
};

Route::middleware(['auth', 'verified'])->group(function () use ($dashboardRedirect) {
    Route::get('dashboard', $dashboardRedirect)->name('dashboard.redirect');
    Route::get('app', $dashboardRedirect)->name('app.redirect');
});

require __DIR__.'/settings.php';
