<?php

use App\Http\Controllers\Api\Courier\AuthController;
use App\Http\Controllers\Api\Courier\DeliveryController;
use App\Http\Controllers\Api\Courier\PickupController;
use App\Http\Controllers\Api\HubController;
use Illuminate\Support\Facades\Route;

// Public: sorting centers a rider can apply to
Route::get('/hubs', [HubController::class, 'index'])->middleware('throttle:60,1');

Route::prefix('courier')->group(function () {

    // Registration and sign-in (no token yet)
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Signed in as a courier, any approval state (pending / rejected / approved)
    Route::middleware(['auth:sanctum', 'courier'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/reapply', [AuthController::class, 'reapply']);
    });

    // Approved, active courier of an approved hub
    Route::middleware(['auth:sanctum', 'courier:approved'])->group(function () {
        Route::get('/pickups', [PickupController::class, 'index']);
        Route::post('/pickups/{order}/accept', [PickupController::class, 'accept']);
        Route::post('/pickups/{order}/decline', [PickupController::class, 'decline']);
        Route::post('/pickups/{order}/confirm', [PickupController::class, 'confirm']);

        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::post('/deliveries/{order}/out', [DeliveryController::class, 'out']);
        Route::post('/deliveries/{order}/delivered', [DeliveryController::class, 'delivered']);
        Route::post('/deliveries/{order}/failed', [DeliveryController::class, 'failed']);
    });
});
