<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use Modules\Bookings\Http\Controllers\AvailabilityController;
use Modules\Bookings\Http\Controllers\BookingInquiryController;
use Modules\Bookings\Http\Controllers\CustomerActivityController;
use Modules\Bookings\Http\Controllers\CustomerController;
use Modules\Bookings\Http\Controllers\CustomerPipelineController;
use Modules\Bookings\Http\Controllers\CustomerTagController;
use Modules\Bookings\Http\Controllers\CustomerTaskController;
use Modules\Bookings\Http\Controllers\ManualBookingController;
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

        Route::get('/create', [ManualBookingController::class, 'create'])->name('create');
        Route::post('/create', [ManualBookingController::class, 'store'])->name('create.store');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::post('/customers/tags', [CustomerTagController::class, 'store'])
            ->name('customers.tags.store');
        Route::delete('/customers/tags/{tag}', [CustomerTagController::class, 'destroy'])
            ->name('customers.tags.destroy');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/tags', [CustomerTagController::class, 'sync'])
            ->name('customers.tags.sync');
        Route::patch('/customers/{customer}/pipeline', [CustomerPipelineController::class, 'update'])
            ->name('customers.pipeline.update');
        Route::post('/customers/{customer}/activities', [CustomerActivityController::class, 'store'])
            ->name('customers.activities.store');
        Route::delete('/customers/{customer}/activities/{activity}', [CustomerActivityController::class, 'destroy'])
            ->name('customers.activities.destroy');
        Route::post('/customers/{customer}/tasks', [CustomerTaskController::class, 'store'])
            ->name('customers.tasks.store');
        Route::patch('/customers/{customer}/tasks/{task}', [CustomerTaskController::class, 'update'])
            ->name('customers.tasks.update');
        Route::patch('/customers/{customer}/tasks/{task}/complete', [CustomerTaskController::class, 'complete'])
            ->name('customers.tasks.complete');
        Route::patch('/customers/{customer}/tasks/{task}/cancel', [CustomerTaskController::class, 'cancel'])
            ->name('customers.tasks.cancel');

        Route::get('/availability/{listing}', [AvailabilityController::class, 'edit'])->name('availability.edit');
        Route::post('/availability/{listing}/block', [AvailabilityController::class, 'block'])->name('availability.block');
        Route::post('/availability/{listing}/block-range', [AvailabilityController::class, 'blockRange'])->name('availability.block-range');
        Route::delete('/availability/{listing}/block-range', [AvailabilityController::class, 'unblockRange'])->name('availability.unblock-range');
        Route::delete('/availability/{listing}/block/{date}', [AvailabilityController::class, 'unblock'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('availability.unblock');

        Route::get('/inquiries/{inquiry}', [BookingInquiryController::class, 'show'])->name('show');
        Route::get('/inquiries/{inquiry}/edit', [BookingInquiryController::class, 'edit'])->name('inquiries.edit');
        Route::patch('/inquiries/{inquiry}', [BookingInquiryController::class, 'update'])->name('inquiries.update');
        Route::patch('/inquiries/{inquiry}/contacted', [BookingInquiryController::class, 'contacted'])->name('inquiries.contacted');
        Route::patch('/inquiries/{inquiry}/accept', [BookingInquiryController::class, 'accept'])->name('inquiries.accept');
        Route::patch('/inquiries/{inquiry}/decline', [BookingInquiryController::class, 'decline'])->name('inquiries.decline');
        Route::patch('/inquiries/{inquiry}/cancel', [BookingInquiryController::class, 'cancel'])->name('inquiries.cancel');

        Route::post('/inquiries/{inquiry}/deposits', [BookingInquiryController::class, 'storeDeposit'])->name('deposits.store');
        Route::patch('/inquiries/{inquiry}/deposits/{deposit}', [BookingInquiryController::class, 'updateDeposit'])->name('deposits.update');
    });
