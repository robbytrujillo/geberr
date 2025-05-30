<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\DriverTrackingController;
use App\Http\Controllers\API\DriverController;
use App\Models\Booking;

Route::post('login', [AuthController::class, 'login'])->name('login');

Route::post('register', [AuthController::class, 'register'])->name('register');

// Route Middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user'])->name('user');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('booking')->group(function () {
        Route::post('price-check', [BookingController::class, 'priceCheck']);
        Route::post('/', [BookingController::class, 'store'])->name('booking');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('booking.cancel');
        Route::get('/', [BookingController::class, 'getAll'])->name('bookings');
        Route::get('/active', [BookingController::class, 'getActiveBooking'])->name('booking.active');
        Route::get('/{booking_id}', [BookingController::class, 'show'])->name('booking.show');
    });

    Route::prefix('driver')->group(function () {
        Route::post('settings', [SettingController::class, 'index'])->name('driver.settings');
        Route::post('booking/{booking_id}/accept', [BookingController::class, 'acceptBooking'])->name('driver.booking.accept');
        Route::put('booking/{booking}/status}', [BookingController::class, 'updateStatus'])->name('driver.booking.status');
        Route::post('tracking', [DriverTrackingController::class, 'store'])->name('driver.tracking.store');
        Route::post('driver/toggle-active', [DriverController::class, 'toggleActive'])->name('driver.toggle-active');
    });
});
