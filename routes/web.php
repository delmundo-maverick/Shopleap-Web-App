<?php

use Illuminate\Support\Facades\Route;

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

// Login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');


// Registration Selection
Route::get('/register', function () {
    return view('auth.register');
})->name('register');


// Buyer Registration
Route::get('/register/buyer', function () {
    return view('auth.register.buyer');
})->name('register.buyer');


// Seller Registration
Route::get('/register/seller', function () {
    return view('auth.register.seller');
})->name('register.seller');


// Logistics Registration
Route::get('/register/logistics', function () {
    return view('auth.register.logistics');
})->name('register.logistics');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});
