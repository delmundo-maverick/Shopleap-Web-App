<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessagingController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\BuyerRegistrationController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Auth\LogisticsRegistrationController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Admin\BuyerManagementController;
use App\Http\Controllers\Admin\LogisticsManagementController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\PolicyController;

use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Logistics\RiderController;
use App\Http\Controllers\Logistics\PickupController;
use App\Http\Controllers\Logistics\IncomingParcelController;
use App\Http\Controllers\Logistics\ServiceAreaController;
use App\Http\Controllers\Logistics\SortingController;
use App\Http\Controllers\Logistics\DeliveryController;

use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\WaybillController;
use App\Http\Controllers\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Seller\AccountController as SellerAccountController;

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

Route::get('/', [App\Http\Controllers\LandingController::class, 'index'])->name('home');

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

Route::get('/policies/{policy}', [PolicyController::class, 'show'])->name('policies.show');

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

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export.csv');

    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    Route::get('/settings', [PlatformSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/general', [PlatformSettingController::class, 'updateGeneral'])->name('settings.general');
    Route::post('/settings/announcements', [PlatformSettingController::class, 'storeAnnouncement'])->name('settings.announcements.store');
    Route::post('/settings/announcements/{announcement}', [PlatformSettingController::class, 'updateAnnouncement'])->name('settings.announcements.update');
    Route::post('/settings/announcements/{announcement}/toggle', [PlatformSettingController::class, 'toggleAnnouncement'])->name('settings.announcements.toggle');
    Route::delete('/settings/announcements/{announcement}', [PlatformSettingController::class, 'destroyAnnouncement'])->name('settings.announcements.destroy');
    Route::post('/settings/policies/{policy}', [PlatformSettingController::class, 'updatePolicy'])->name('settings.policies.update');
});


/*
|--------------------------------------------------------------------------
| Logistics / Sorting Center Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'logistics'])->prefix('logistics')->name('logistics.')->group(function () {

    Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])->name('dashboard');

    Route::get('/riders', [RiderController::class, 'index'])->name('riders.index');
    Route::post('/riders/{rider}/approve', [RiderController::class, 'approve'])->name('riders.approve');
    Route::post('/riders/{rider}/reject', [RiderController::class, 'reject'])->name('riders.reject');

    Route::post('/riders/{rider}/toggle-active', [RiderController::class, 'toggleActive'])->name('riders.toggle-active');
    Route::get('/pickups', [PickupController::class, 'index'])->name('pickups.index');
    Route::post('/pickups/{order}/confirm', [PickupController::class, 'confirmPickup'])->name('pickups.confirm');

    Route::get('/incoming', [IncomingParcelController::class, 'index'])->name('incoming.index');
    Route::post('/incoming/{order}/confirm-arrival', [IncomingParcelController::class, 'confirmArrival'])->name('incoming.confirm-arrival');

    Route::post('/pickups/{order}/assign', [PickupController::class, 'assign'])->name('pickups.assign');
    Route::post('/pickups/{order}/decline', [PickupController::class, 'decline'])->name('pickups.decline');

    Route::get('/areas', [ServiceAreaController::class, 'index'])->name('areas.index');
    Route::post('/areas', [ServiceAreaController::class, 'store'])->name('areas.store');
    Route::post('/areas/{area}', [ServiceAreaController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{area}', [ServiceAreaController::class, 'destroy'])->name('areas.destroy');

    Route::get('/sorting', [SortingController::class, 'index'])->name('sorting.index');
    Route::post('/sorting/sort-all', [SortingController::class, 'sortAll'])->name('sorting.sort-all');
    Route::post('/sorting/assign-area', [SortingController::class, 'assignArea'])->name('sorting.assign-area');
    Route::post('/sorting/{order}/sort', [SortingController::class, 'sort'])->name('sorting.sort');
    Route::post('/sorting/{order}/assign', [SortingController::class, 'assign'])->name('sorting.assign');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('/deliveries/{order}/out', [DeliveryController::class, 'outForDelivery'])->name('deliveries.out');
    Route::post('/deliveries/{order}/delivered', [DeliveryController::class, 'delivered'])->name('deliveries.delivered');
    Route::post('/deliveries/{order}/failed', [DeliveryController::class, 'failed'])->name('deliveries.failed');
    Route::post('/deliveries/{order}/reschedule', [DeliveryController::class, 'reschedule'])->name('deliveries.reschedule');
    Route::post('/deliveries/{order}/return', [DeliveryController::class, 'returnToSeller'])->name('deliveries.return');
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

    Route::get('/reports', [SellerReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/csv', [SellerReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [SellerReportController::class, 'exportPdf'])->name('reports.export.pdf');

    Route::get('/orders/{order}/waybill', [WaybillController::class, 'show'])->name('orders.waybill');

    Route::get('/account', [SellerAccountController::class, 'show'])->name('account.index');
    Route::post('/account/profile', [SellerAccountController::class, 'updateProfile'])->name('account.profile');
    Route::post('/account/avatar', [SellerAccountController::class, 'updateAvatar'])->name('account.avatar');
    Route::delete('/account/avatar', [SellerAccountController::class, 'destroyAvatar'])->name('account.avatar.destroy');
    Route::post('/account/address', [SellerAccountController::class, 'updateAddress'])->name('account.address');
    Route::post('/account/password', [SellerAccountController::class, 'updatePassword'])->name('account.password');

    Route::post('/orders/{order}/request-pickup', [SellerOrderController::class, 'requestPickup'])->name('orders.request-pickup');
    Route::post('/orders/{order}/cancel-pickup', [SellerOrderController::class, 'cancelPickup'])->name('orders.cancel-pickup');
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


Route::middleware(['auth'])->prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [MessagingController::class, 'index'])->name('index');
    Route::get('/contacts', [MessagingController::class, 'contacts'])->name('contacts');
    Route::post('/', [MessagingController::class, 'store'])->name('store');
    Route::get('/{conversation}/messages', [MessagingController::class, 'messages'])->name('thread');
    Route::post('/{conversation}/messages', [MessagingController::class, 'storeMessage'])->name('thread.store');
    Route::post('/forward/{message}', [MessagingController::class, 'forward'])->name('forward');
    Route::post('/open', [MessagingController::class, 'open'])->name('open');
    Route::get('/recent-purchases', [MessagingController::class, 'recentPurchases'])->name('recent-purchases');
    Route::get('/widget-data', [MessagingController::class, 'widgetData'])->name('widget-data');
    Route::get('/support', [MessagingController::class, 'openSupport'])->name('support');
    Route::post('/{conversation}/delete', [MessagingController::class, 'destroy'])->name('destroy');
    Route::get('/unread-count', [MessagingController::class, 'unreadCount'])->name('unread-count');
    Route::post('/{conversation}/claim', [MessagingController::class, 'claim'])->name('claim');
    Route::post('/{conversation}/unclaim', [MessagingController::class, 'unclaim'])->name('unclaim');
    Route::post('/{conversation}/resolve', [MessagingController::class, 'resolve'])->name('resolve');
    Route::post('/{conversation}/reopen', [MessagingController::class, 'reopen'])->name('reopen');
});
