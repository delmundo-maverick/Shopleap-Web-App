<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{
    LandingController,
    MessagingController,
    PolicyController,
    SupportController,
};

use App\Http\Controllers\Auth\{
    BuyerRegistrationController,
    LoginController,
    LogisticsRegistrationController,
    SellerRegistrationController,
};

use App\Http\Controllers\Admin\{
    ActivityLogController,
    BuyerManagementController,
    DashboardController as AdminDashboardController,
    LogisticsManagementController,
    PlatformSettingController,
    SellerManagementController,
    TransactionController,
};

use App\Http\Controllers\Logistics\{
    DashboardController as LogisticsDashboardController,
    DeliveryController,
    IncomingParcelController,
    PickupController,
    RiderController,
    ServiceAreaController,
    SortingController,
};

use App\Http\Controllers\Seller\{
    AccountController as SellerAccountController,
    DashboardController as SellerDashboardController,
    OrderController as SellerOrderController,
    ProductController as SellerProductController,
    ReportController as SellerReportController,
    ReviewController as SellerReviewController,
    WaybillController,
};

use App\Http\Controllers\Buyer\{
    CartController,
    CheckoutController,
    HomeController as BuyerHomeController,
    OrderController as BuyerOrderController,
    ProductController as BuyerProductController,
    ReviewController as BuyerReviewController,
    ShopController,
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/policies/{policy}', [PolicyController::class, 'show'])->name('policies.show');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store');
    Route::post('/logout', 'destroy')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Registration (role selection → role form → OTP → profile)
|--------------------------------------------------------------------------
*/

Route::view('/register', 'auth.register')->name('register');

Route::prefix('register')->group(function () {

    // Step 1: Role-specific registration form
    Route::controller(BuyerRegistrationController::class)->group(function () {
        Route::get('/buyer', 'create')->name('register.buyer');
        Route::post('/buyer', 'store');
    });

    Route::controller(SellerRegistrationController::class)->group(function () {
        Route::get('/seller', 'create')->name('register.seller');
        Route::post('/seller', 'store');
    });

    Route::controller(LogisticsRegistrationController::class)->group(function () {
        Route::get('/logistics', 'create')->name('register.logistics');
        Route::post('/logistics', 'store');
    });

    // Steps 2 & 3: OTP verification and profile completion
    Route::prefix('{role}')
        ->where(['role' => 'buyer|seller|logistics'])
        ->controller(LoginController::class)
        ->group(function () {

            // Step 2: OTP
            Route::get('/verify', 'showOtpForm')->name('register.otp.form');
            Route::post('/verify', 'verifyOtp')->name('register.otp.verify');
            Route::post('/resend-otp', 'resendOtp')
                ->middleware('throttle:3,1')
                ->name('register.resend-otp');

            // Step 3: Profile (requires the OTP-created user to be logged in)
            Route::middleware('auth')->group(function () {
                Route::get('/profile', 'showProfileForm')->name('register.profile.form');
                Route::post('/profile', 'storeProfile')->name('register.profile.store');
            });
        });
});

Route::view('/registration/pending', 'auth.registration-pending')
    ->middleware('auth')
    ->name('registration.pending');

/*
|--------------------------------------------------------------------------
| Help / Support (any authenticated user)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('help')
    ->name('help.')
    ->controller(SupportController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/tickets', 'store')
            ->middleware('throttle:10,1')
            ->name('tickets.store');
    });

/*
|--------------------------------------------------------------------------
| Messaging (any authenticated user)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('messages')
    ->name('messages.')
    ->controller(MessagingController::class)
    ->group(function () {

        // Inbox & lookups
        Route::get('/', 'index')->name('index');
        Route::get('/contacts', 'contacts')->name('contacts');
        Route::get('/recent-purchases', 'recentPurchases')->name('recent-purchases');
        Route::get('/widget-data', 'widgetData')->name('widget-data');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::get('/support', 'openSupport')->name('support');

        // Create / open conversations
        Route::post('/', 'store')->name('store');
        Route::post('/open', 'open')->name('open');
        Route::post('/forward/{message}', 'forward')->name('forward');

        // Single conversation
        Route::prefix('{conversation}')->group(function () {
            Route::get('/messages', 'messages')->name('thread');
            Route::post('/messages', 'storeMessage')->name('thread.store');
            Route::get('/details', 'details')->name('details');
            Route::post('/delete', 'destroy')->name('destroy');

            // Support-agent actions
            Route::post('/claim', 'claim')->name('claim');
            Route::post('/unclaim', 'unclaim')->name('unclaim');
            Route::post('/resolve', 'resolve')->name('resolve');
            Route::post('/reopen', 'reopen')->name('reopen');
        });
    });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Registration management (sellers, buyers, logistics share the same shape)
    $registrations = [
        'sellers'   => [SellerManagementController::class,    'sellerProfile'],
        'buyers'    => [BuyerManagementController::class,     'buyerProfile'],
        'logistics' => [LogisticsManagementController::class, 'logisticsProfile'],
    ];

    Route::prefix('registrations')->name('registrations.')->group(function () use ($registrations) {
        foreach ($registrations as $type => [$controller, $param]) {
            Route::prefix($type)
                ->name("{$type}.")
                ->controller($controller)
                ->group(function () use ($param) {
                    Route::get('/', 'index')->name('index');
                    Route::get("/{{$param}}/details", 'details')->name('details');
                    Route::post("/{{$param}}/approve", 'approve')->name('approve');
                    Route::post("/{{$param}}/reject", 'reject')->name('reject');
                    Route::post("/{{$param}}/account-status", 'updateAccountStatus')->name('accountStatus');
                });
        }
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->controller(TransactionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export/csv', 'exportCsv')->name('export.csv');
    });

    // Activity logs
    Route::get('/logs', [ActivityLogController::class, 'index'])->name('logs.index');

    // Platform settings
    Route::prefix('settings')->name('settings.')->controller(PlatformSettingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/general', 'updateGeneral')->name('general');
        Route::post('/policies/{policy}', 'updatePolicy')->name('policies.update');

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::post('/', 'storeAnnouncement')->name('store');
            Route::post('/{announcement}', 'updateAnnouncement')->name('update');
            Route::post('/{announcement}/toggle', 'toggleAnnouncement')->name('toggle');
            Route::delete('/{announcement}', 'destroyAnnouncement')->name('destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Logistics / Sorting Center Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'logistics'])->prefix('logistics')->name('logistics.')->group(function () {

    Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])->name('dashboard');

    // Riders
    Route::prefix('riders')->name('riders.')->controller(RiderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{rider}/approve', 'approve')->name('approve');
        Route::post('/{rider}/reject', 'reject')->name('reject');
        Route::post('/{rider}/toggle-active', 'toggleActive')->name('toggle-active');
    });

    // Pickups
    Route::prefix('pickups')->name('pickups.')->controller(PickupController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{order}/confirm', 'confirmPickup')->name('confirm');
        Route::post('/{order}/assign', 'assign')->name('assign');
        Route::post('/{order}/decline', 'decline')->name('decline');
    });

    // Incoming parcels
    Route::prefix('incoming')->name('incoming.')->controller(IncomingParcelController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{order}/confirm-arrival', 'confirmArrival')->name('confirm-arrival');
    });

    // Service areas
    Route::prefix('areas')->name('areas.')->controller(ServiceAreaController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/{area}', 'update')->name('update');
        Route::delete('/{area}', 'destroy')->name('destroy');
    });

    // Sorting
    Route::prefix('sorting')->name('sorting.')->controller(SortingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/sort-all', 'sortAll')->name('sort-all');
        Route::post('/assign-area', 'assignArea')->name('assign-area');
        Route::post('/{order}/sort', 'sort')->name('sort');
        Route::post('/{order}/assign', 'assign')->name('assign');
    });

    // Deliveries
    Route::prefix('deliveries')->name('deliveries.')->controller(DeliveryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{order}/out', 'outForDelivery')->name('out');
        Route::post('/{order}/delivered', 'delivered')->name('delivered');
        Route::post('/{order}/failed', 'failed')->name('failed');
        Route::post('/{order}/reschedule', 'reschedule')->name('reschedule');
        Route::post('/{order}/return', 'returnToSeller')->name('return');
    });
});

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'seller'])->prefix('seller')->name('seller.')->group(function () {

    Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::prefix('products')->name('products.')->controller(SellerProductController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{product}/details', 'details')->name('details');
        Route::post('/{product}', 'update')->name('update');
        Route::post('/{product}/status', 'updateStatus')->name('status');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::controller(SellerOrderController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{order}', 'show')->name('show');
            Route::post('/{order}/status', 'updateStatus')->name('status');
            Route::post('/{order}/request-pickup', 'requestPickup')->name('request-pickup');
            Route::post('/{order}/cancel-pickup', 'cancelPickup')->name('cancel-pickup');
        });

        Route::get('/{order}/waybill', [WaybillController::class, 'show'])->name('waybill');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->controller(SellerReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export/csv', 'exportCsv')->name('export.csv');
        Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
    });

    // Reviews
    Route::prefix('reviews')->name('reviews.')->controller(SellerReviewController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{review}/reply', 'reply')->middleware('throttle:30,1')->name('reply');
        Route::delete('/{review}/reply', 'destroyReply')->name('reply.destroy');
    });

    // Account
    Route::prefix('account')->name('account.')->controller(SellerAccountController::class)->group(function () {
        Route::get('/', 'show')->name('index');
        Route::post('/profile', 'updateProfile')->name('profile');
        Route::post('/address', 'updateAddress')->name('address');
        Route::post('/password', 'updatePassword')->name('password');
        Route::post('/avatar', 'updateAvatar')->name('avatar');
        Route::delete('/avatar', 'destroyAvatar')->name('avatar.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Buyer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'buyer'])->prefix('buyer')->name('buyer.')->group(function () {

    Route::get('/', [BuyerHomeController::class, 'index'])->name('home');

    // Browsing
    Route::get('/products', [BuyerProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [BuyerProductController::class, 'show'])->name('products.show');
    Route::get('/shop/{seller}', [ShopController::class, 'show'])->name('shop.show');

    // Cart
    Route::controller(CartController::class)->name('cart.')->group(function () {
        Route::get('/cart', 'index')->name('index');
        Route::post('/cart/{product}', 'store')->name('store');
        Route::post('/cart/item/{cartItem}', 'update')->name('update');
        Route::delete('/cart/item/{cartItem}', 'destroy')->name('destroy');
        Route::delete('/cart', 'destroyMany')->name('destroyMany');
    });

    // Checkout
    Route::controller(CheckoutController::class)->prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::controller(BuyerOrderController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{order}/confirm', 'confirmReceived')->name('confirm');
            Route::post('/{order}/buy-again', 'buyAgain')->name('buy-again');
        });

        // Review a purchased item (only allowed once the order is COMPLETED)
        Route::post('/{order}/items/{item}/review', [BuyerReviewController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('review.store');
    });
});
