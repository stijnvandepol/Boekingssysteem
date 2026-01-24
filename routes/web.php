<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBookingController::class, 'index'])->name('booking.index');
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
})->name('health');
Route::post('/booking', [PublicBookingController::class, 'store'])
    ->middleware(['throttle:booking', 'turnstile'])
    ->name('booking.store');
Route::get('/booking/confirmed', [PublicBookingController::class, 'confirmed'])->name('booking.confirmed');
Route::get('/booking/{booking}/ics', [PublicBookingController::class, 'ics'])
    ->middleware('signed')
    ->name('booking.ics');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('public.login');
    })->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('', [AuthController::class, 'showLogin'])->name('login');
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin', 'throttle:admin'])->group(function () {
        Route::get('', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('availability', [AvailabilityController::class, 'store'])->name('availability.store');
        Route::get('availability/{block}/edit', [AvailabilityController::class, 'edit'])->name('availability.edit');
        Route::put('availability/{block}', [AvailabilityController::class, 'update'])->name('availability.update');
        Route::delete('availability/{block}', [AvailabilityController::class, 'destroy'])->name('availability.destroy');
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::put('resource', [ResourceController::class, 'update'])->name('resource.update');
    });
});
