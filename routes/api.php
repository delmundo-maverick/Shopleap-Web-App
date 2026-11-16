<?php

use App\Http\Controllers\Api\Courier\AccountController;
use App\Http\Controllers\Api\Courier\AuthController;
use App\Http\Controllers\Api\Courier\CashController;
use App\Http\Controllers\Api\Courier\ChatController;
use App\Http\Controllers\Api\Courier\DashboardController;
use App\Http\Controllers\Api\Courier\DeliveryController;
use App\Http\Controllers\Api\Courier\EarningsController;
use App\Http\Controllers\Api\Courier\HistoryController;
use App\Http\Controllers\Api\Courier\NotificationController;
use App\Http\Controllers\Api\Courier\OrderScanController;
use App\Http\Controllers\Api\Courier\PickupController;
use App\Http\Controllers\Api\HubController;
use App\Http\Controllers\MoneyReceiptController;
use Illuminate\Support\Facades\Route;

// Public: sorting centers a rider can apply to.
Route::get('/hubs', [HubController::class, 'index'])
    ->middleware('throttle:60,1');

// Public: the barangays inside a hub's service areas.
Route::get('/hubs/{hub}/barangays', [HubController::class, 'barangays'])
    ->middleware('throttle:60,1');

Route::prefix('courier')->group(function () {

    // Registration and sign-in (no token required).
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:10,1');

    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:3,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // Password recovery (no token required).
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:10,1');

    // Signed-in couriers, regardless of approval state.
    // Pending, rejected, and approved couriers can access these routes.
    Route::middleware(['auth:sanctum', 'courier', 'throttle:courier-reads', 'throttle:mutations'])->group(function () {

        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/reapply', [AuthController::class, 'reapply']);

        // Account management.
        Route::post('/account/profile', [AccountController::class, 'updateProfile']);

        Route::post('/account/password', [AccountController::class, 'updatePassword'])
            ->middleware('throttle:10,1');

        Route::post('/account/avatar', [AccountController::class, 'updateAvatar']);
        Route::delete('/account/avatar', [AccountController::class, 'destroyAvatar']);
    });

    // Approved, active couriers of approved hubs.
    Route::middleware(['auth:sanctum', 'courier:approved', 'throttle:courier-reads', 'throttle:mutations'])->group(function () {

        Route::get('/orders/scan/{code}', OrderScanController::class)
            ->where('code', '.+')
            ->name('api.courier.orders.scan');

        // Pickups.
        Route::get('/pickups', [PickupController::class, 'index']);
        Route::post('/pickups/{order}/accept', [PickupController::class, 'accept']);
        Route::post('/pickups/{order}/decline', [PickupController::class, 'decline']);
        Route::post('/pickups/{order}/confirm', [PickupController::class, 'confirm']);

        // Deliveries.
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::post('/deliveries/{order}/out', [DeliveryController::class, 'out']);
        Route::post('/deliveries/{order}/delivered', [DeliveryController::class, 'delivered']);
        Route::post('/deliveries/{order}/failed', [DeliveryController::class, 'failed']);

        // Dashboard, history, and earnings.
        Route::get('/dashboard', [DashboardController::class, 'summary']);
        Route::get('/history', [HistoryController::class, 'index']);
        Route::get('/earnings', [EarningsController::class, 'index']);
        Route::get('/earnings/rate', [EarningsController::class, 'rate']);
        Route::get('/earnings/summary', [EarningsController::class, 'index']);
        Route::get('/earnings/history', [EarningsController::class, 'history'])->name('api.courier.earnings.history');
        Route::get('/cash/summary', [CashController::class, 'summary'])->name('api.courier.cash.summary');
        Route::get('/cash/history', [CashController::class, 'history'])->name('api.courier.cash.history');
        Route::get('/receipts/{type}/{reference}', [MoneyReceiptController::class, 'show'])->whereIn('type', ['cash-handovers', 'rider-payouts'])->name('api.courier.receipts.show');

        // Notifications.
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

        // Chat and messaging.
        Route::prefix('chat')->group(function () {
            Route::get('/contacts', [ChatController::class, 'contacts']);
            Route::get('/conversations', [ChatController::class, 'conversations']);
            Route::get('/unread-count', [ChatController::class, 'unreadCount']);
            Route::post('/open', [ChatController::class, 'open']);

            Route::get('/{conversation}/messages', [ChatController::class, 'messages']);

            Route::post('/{conversation}/messages', [ChatController::class, 'store'])
                ->middleware('throttle:60,1');

            Route::post('/{conversation}/hide', [ChatController::class, 'destroy']);
        });
    });
});
