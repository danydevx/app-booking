<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Modules\Listings\Http\Controllers\ListingController;
use Modules\Listings\Http\Controllers\ListingMediaController;
use Modules\Listings\Http\Controllers\ListingPackageController;
use Modules\Listings\Http\Controllers\ListingPriceRangeController;
use Modules\Listings\Http\Controllers\ListingPricingController;
use Modules\Listings\Http\Controllers\ListingPromotionController;
use Modules\Listings\Http\Controllers\ListingServiceController;
use Modules\Listings\Http\Controllers\PublicListingController;

Route::middleware('web')->group(function (): void {
    Route::get('terrazas', [PublicListingController::class, 'index'])
        ->name('public.listings.index');
    Route::get('terrazas/{listing:slug}', [PublicListingController::class, 'show'])
        ->name('public.listings.show');
});

Route::prefix('app/{current_team}/listings')
    ->middleware(['web', 'auth', 'verified', EnsureTeamMembership::class])
    ->name('listings.')
    ->group(function (): void {
        Route::get('/', [ListingController::class, 'index'])->name('index');
        Route::get('/create', [ListingController::class, 'create'])->name('create');
        Route::post('/', [ListingController::class, 'store'])->name('store');
        Route::get('/{listing}', [ListingController::class, 'manage'])->name('manage');
        Route::get('/{listing}/edit', [ListingController::class, 'edit'])->name('edit');
        Route::get('/{listing}/pricing', [ListingPricingController::class, 'edit'])->name('pricing.edit');
        Route::patch('/{listing}/pricing', [ListingPricingController::class, 'update'])->name('pricing.update');
        Route::post('/{listing}/pricing/ranges', [ListingPriceRangeController::class, 'store'])->name('pricing.ranges.store');
        Route::patch('/{listing}/pricing/ranges/{range}', [ListingPriceRangeController::class, 'update'])->name('pricing.ranges.update');
        Route::delete('/{listing}/pricing/ranges/{range}', [ListingPriceRangeController::class, 'destroy'])->name('pricing.ranges.destroy');

        Route::get('/{listing}/packages', [ListingPackageController::class, 'index'])->name('packages.index');
        Route::post('/{listing}/packages', [ListingPackageController::class, 'store'])->name('packages.store');
        Route::patch('/{listing}/packages/{package}', [ListingPackageController::class, 'update'])->name('packages.update');
        Route::delete('/{listing}/packages/{package}', [ListingPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/{listing}/services', [ListingServiceController::class, 'index'])->name('services.index');
        Route::post('/{listing}/services', [ListingServiceController::class, 'store'])->name('services.store');
        Route::patch('/{listing}/services/{service}', [ListingServiceController::class, 'update'])->name('services.update');
        Route::delete('/{listing}/services/{service}', [ListingServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/{listing}/promotions', [ListingPromotionController::class, 'index'])->name('promotions.index');
        Route::post('/{listing}/promotions', [ListingPromotionController::class, 'store'])->name('promotions.store');
        Route::patch('/{listing}/promotions/{promotion}', [ListingPromotionController::class, 'update'])->name('promotions.update');
        Route::delete('/{listing}/promotions/{promotion}', [ListingPromotionController::class, 'destroy'])->name('promotions.destroy');
        Route::match(['put', 'patch'], '/{listing}', [ListingController::class, 'update'])->name('update');
        Route::delete('/{listing}', [ListingController::class, 'destroy'])->name('destroy');
        Route::patch('/{listing}/publish', [ListingController::class, 'publish'])->name('publish');
        Route::patch('/{listing}/unpublish', [ListingController::class, 'unpublish'])->name('unpublish');

        Route::post('/{listing}/media', [ListingMediaController::class, 'store'])->name('media.store');
        Route::patch('/{listing}/media/reorder', [ListingMediaController::class, 'reorder'])->name('media.reorder');
        Route::delete('/{listing}/media/{media}', [ListingMediaController::class, 'destroy'])->name('media.destroy');
    });
