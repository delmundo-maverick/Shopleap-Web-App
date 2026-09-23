<?php
// FILE PATH: routes/web.php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\BuyerRegistrationController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Auth\LogisticsRegistrationController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Admin\BuyerManagementController;
use App\Http\Controllers\Admin\LogisticsManagementController;

use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;

use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;

use App\Http\Controllers\Buyer\HomeController as BuyerHomeController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\CheckoutController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ShopController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing.index');
})->name('home');


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Buyer Registration
Route::get('/register/buyer', [BuyerRegistrationController::class, 'create'])->name('register.buyer');
Route::post('/register/buyer', [BuyerRegistrationController::class, 'store']);

// Seller Registration
Route::get('/register/seller', [SellerRegistrationController::class, 'create'])->name('register.seller');
Route::post('/register/seller', [SellerRegistrationController::class, 'store']);

// Logistics Registration (Fixed duplicate route definition)
Route::get('/register/logistics', [LogisticsRegistrationController::class, 'create'])->name('register.logistics');
Route::post('/register/logistics', [LogisticsRegistrationController::class, 'store']);

/*
|--------------------------------------------------------------------------
| OTP Verification + Profile Completion
|--------------------------------------------------------------------------
*/

Route::prefix('register/{role}')->where(['role' => 'buyer|seller|logistics'])->group(function () {
    // Step 2: OTP
    Route::get('/verify', [LoginController::class, 'showOtpForm'])->name('register.otp.form');
    Route::post('/verify', [LoginController::class, 'verifyOtp'])->name('register.otp.verify');
    Route::post('/resend-otp', [LoginController::class, 'resendOtp'])
        ->middleware('throttle:3,1')
        ->name('register.resend-otp');

    // Step 3: Profile (requires the OTP-created user to be logged in)
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [LoginController::class, 'showProfileForm'])->name('register.profile.form');
        Route::post('/profile', [LoginController::class, 'storeProfile'])->name('register.profile.store');
    });
});

Route::get('/registration/pending', fn() => view('auth.registration-pending'))
    ->middleware('auth')
    ->name('registration.pending');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Seller Registrations Management
    Route::prefix('registrations/sellers')->name('registrations.sellers.')->group(function () {
        Route::get('/', [SellerManagementController::class, 'index'])->name('index');
        Route::get('/{sellerProfile}/details', [SellerManagementController::class, 'details'])->name('details');
        Route::post('/{sellerProfile}/approve', [SellerManagementController::class, 'approve'])->name('approve');
        Route::post('/{sellerProfile}/reject', [SellerManagementController::class, 'reject'])->name('reject');
        Route::post('/{sellerProfile}/account-status', [SellerManagementController::class, 'updateAccountStatus'])->name('accountStatus');
    });

    // Buyer Registrations Management
    Route::prefix('registrations/buyers')->name('registrations.buyers.')->group(function () {
        Route::get('/', [BuyerManagementController::class, 'index'])->name('index');
        Route::get('/{buyerProfile}/details', [BuyerManagementController::class, 'details'])->name('details');
        Route::post('/{buyerProfile}/approve', [BuyerManagementController::class, 'approve'])->name('approve');
        Route::post('/{buyerProfile}/reject', [BuyerManagementController::class, 'reject'])->name('reject');
        Route::post('/{buyerProfile}/account-status', [BuyerManagementController::class, 'updateAccountStatus'])->name('accountStatus');
    });

    // Logistics Registrations Management (Moved inside admin group to fix route name)
    Route::prefix('registrations/logistics')->name('registrations.logistics.')->group(function () {
        Route::get('/', [LogisticsManagementController::class, 'index'])->name('index');
        Route::get('/{logisticsProfile}/details', [LogisticsManagementController::class, 'details'])->name('details');
        Route::post('/{logisticsProfile}/approve', [LogisticsManagementController::class, 'approve'])->name('approve');
        Route::post('/{logisticsProfile}/reject', [LogisticsManagementController::class, 'reject'])->name('reject');
        Route::post('/{logisticsProfile}/account-status', [LogisticsManagementController::class, 'updateAccountStatus'])->name('accountStatus');
    });
});


/*
|--------------------------------------------------------------------------
| Logistics / Sorting Center Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'logistics'])->prefix('logistics')->name('logistics.')->group(function () {

    Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])->name('dashboard');
});


/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'seller'])->prefix('seller')->name('seller.')->group(function () {

    Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
    Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/details', [SellerProductController::class, 'details'])->name('products.details');
    Route::post('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/status', [SellerProductController::class, 'updateStatus'])->name('products.status');

    Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [SellerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [SellerOrderController::class, 'updateStatus'])->name('orders.status');
});


/*
|--------------------------------------------------------------------------
| Buyer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'buyer'])->prefix('buyer')->name('buyer.')->group(function () {

    Route::get('/', [BuyerHomeController::class, 'index'])->name('home');

    Route::get('/products/{product}', [BuyerProductController::class, 'show'])->name('products.show');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/item/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/item/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');

    Route::get('/orders', [BuyerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/confirm', [BuyerOrderController::class, 'confirmReceived'])->name('orders.confirm');

    Route::get('/products/{product}', [BuyerProductController::class, 'show'])->name('products.show');
    Route::get('/shop/{seller}', [ShopController::class, 'show'])->name('shop.show');
});
