<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Modules\Listings\Http\Controllers\ListingController;
use Modules\Listings\Http\Controllers\ListingMediaController;
use Modules\Listings\Http\Controllers\PublicListingController;

Route::middleware('web')->group(function (): void {
    Route::get('terrazas', [PublicListingController::class, 'index'])
        ->name('public.listings.index');
    Route::get('terrazas/{listing:slug}', [PublicListingController::class, 'show'])
        ->name('public.listings.show');
});

Route::prefix('{current_team}/listings')
    ->middleware(['web', 'auth', 'verified', EnsureTeamMembership::class])
    ->name('listings.')
    ->group(function (): void {
        Route::get('/', [ListingController::class, 'index'])->name('index');
        Route::get('/create', [ListingController::class, 'create'])->name('create');
        Route::post('/', [ListingController::class, 'store'])->name('store');
        Route::get('/{listing}/edit', [ListingController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{listing}', [ListingController::class, 'update'])->name('update');
        Route::delete('/{listing}', [ListingController::class, 'destroy'])->name('destroy');
        Route::patch('/{listing}/publish', [ListingController::class, 'publish'])->name('publish');
        Route::patch('/{listing}/unpublish', [ListingController::class, 'unpublish'])->name('unpublish');

        Route::post('/{listing}/media', [ListingMediaController::class, 'store'])->name('media.store');
        Route::patch('/{listing}/media/reorder', [ListingMediaController::class, 'reorder'])->name('media.reorder');
        Route::delete('/{listing}/media/{media}', [ListingMediaController::class, 'destroy'])->name('media.destroy');
    });
