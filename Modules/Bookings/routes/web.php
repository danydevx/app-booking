<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Modules\Bookings\Http\Controllers\AvailabilityController;
use Modules\Bookings\Http\Controllers\BookingInquiryController;
use Modules\Bookings\Http\Controllers\CustomerController;
use Modules\Bookings\Http\Controllers\PublicAvailabilityController;

Route::middleware('web')->group(function (): void {
    Route::get('terrazas/{listing:slug}/disponibilidad', [PublicAvailabilityController::class, 'show'])
        ->name('public.bookings.availability');
    Route::post('terrazas/{listing:slug}/solicitud', [PublicAvailabilityController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('public.bookings.inquiries.store');
});

Route::prefix('{current_team}/bookings')
    ->middleware(['web', 'auth', 'verified', EnsureTeamMembership::class])
    ->name('bookings.')
    ->group(function (): void {
        Route::get('/', [BookingInquiryController::class, 'index'])->name('index');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        Route::get('/availability/{listing}', [AvailabilityController::class, 'edit'])->name('availability.edit');
        Route::post('/availability/{listing}/block', [AvailabilityController::class, 'block'])->name('availability.block');
        Route::post('/availability/{listing}/block-range', [AvailabilityController::class, 'blockRange'])->name('availability.block-range');
        Route::delete('/availability/{listing}/block/{date}', [AvailabilityController::class, 'unblock'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('availability.unblock');

        Route::get('/inquiries/{inquiry}', [BookingInquiryController::class, 'show'])->name('show');
        Route::patch('/inquiries/{inquiry}/contacted', [BookingInquiryController::class, 'contacted'])->name('inquiries.contacted');
        Route::patch('/inquiries/{inquiry}/accept', [BookingInquiryController::class, 'accept'])->name('inquiries.accept');
        Route::patch('/inquiries/{inquiry}/decline', [BookingInquiryController::class, 'decline'])->name('inquiries.decline');
        Route::patch('/inquiries/{inquiry}/cancel', [BookingInquiryController::class, 'cancel'])->name('inquiries.cancel');

        Route::post('/inquiries/{inquiry}/deposits', [BookingInquiryController::class, 'storeDeposit'])->name('deposits.store');
        Route::patch('/inquiries/{inquiry}/deposits/{deposit}', [BookingInquiryController::class, 'updateDeposit'])->name('deposits.update');
    });
