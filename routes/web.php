<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\BuyerRegistrationController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Admin\BuyerManagementController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Buyer\HomeController as BuyerHomeController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\CartController;

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

// Registration Selection
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Buyer Registration
Route::get('/register/buyer', [BuyerRegistrationController::class, 'create'])->name('register.buyer');
Route::post('/register/buyer', [BuyerRegistrationController::class, 'store']);

// Seller Registration
Route::get('/register/seller', [SellerRegistrationController::class, 'create'])->name('register.seller');
Route::post('/register/seller', [SellerRegistrationController::class, 'store']);

// Logistics Registration
Route::get('/register/logistics', function () {
    return view('auth.register.logistics');
})->name('register.logistics');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::prefix('registrations/sellers')->name('registrations.sellers.')->group(function () {
        Route::get('/', [SellerManagementController::class, 'index'])->name('index');
        Route::get('/{sellerProfile}/details', [SellerManagementController::class, 'details'])->name('details');
        Route::post('/{sellerProfile}/approve', [SellerManagementController::class, 'approve'])->name('approve');
        Route::post('/{sellerProfile}/reject', [SellerManagementController::class, 'reject'])->name('reject');
        Route::post('/{sellerProfile}/account-status', [SellerManagementController::class, 'updateAccountStatus'])->name('accountStatus');
    });

    Route::prefix('registrations/buyers')->name('registrations.buyers.')->group(function () {
        Route::get('/', [BuyerManagementController::class, 'index'])->name('index');
        Route::get('/{buyerProfile}/details', [BuyerManagementController::class, 'details'])->name('details');
        Route::post('/{buyerProfile}/approve', [BuyerManagementController::class, 'approve'])->name('approve');
        Route::post('/{buyerProfile}/reject', [BuyerManagementController::class, 'reject'])->name('reject');
        Route::post('/{buyerProfile}/account-status', [BuyerManagementController::class, 'updateAccountStatus'])->name('accountStatus');
    });
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
});
