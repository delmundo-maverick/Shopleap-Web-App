<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;

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
Route::get('/register/buyer', function () {
    return view('auth.register.buyer');
})->name('register.buyer');

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
});


/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
});
