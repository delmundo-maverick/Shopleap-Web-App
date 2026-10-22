
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

use App\Http\Controllers\{
    LandingController,
    MessagingController,
    PolicyController,
    SupportController,
    ReturnMediaController,
    NotificationController
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
    ReturnController as AdminReturnController,
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
    ProductDiscountController as SellerProductDiscountController,
    ReturnController as SellerReturnController,
};

use App\Http\Controllers\Buyer\{
    CartController,
    CheckoutController,
    HomeController as BuyerHomeController,
    OrderController as BuyerOrderController,
    ProductController as BuyerProductController,
    ReviewController as BuyerReviewController,
    ShopController,
    AccountController as BuyerAccountController,
    ReturnController as BuyerReturnController,
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('/policies/{policy}', [PolicyController::class, 'show'])
    ->name('policies.show');

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
| Registration
|--------------------------------------------------------------------------
*/

Route::view('/register', 'auth.register')->name('register');

Route::prefix('register')->group(function () {

    // Buyer registration
    Route::controller(BuyerRegistrationController::class)->group(function () {
        Route::get('/buyer', 'create')->name('register.buyer');
        Route::post('/buyer', 'store');
    });

    // Seller registration
    Route::controller(SellerRegistrationController::class)->group(function () {
        Route::get('/seller', 'create')->name('register.seller');
        Route::post('/seller', 'store');
    });

    // Logistics registration
    Route::controller(LogisticsRegistrationController::class)->group(function () {
        Route::get('/logistics', 'create')->name('register.logistics');
        Route::post('/logistics', 'store');
    });

    // OTP verification
    Route::prefix('{role}')
        ->where(['role' => 'buyer|seller|logistics'])
        ->controller(LoginController::class)
        ->group(function () {
            Route::get('/verify', 'showOtpForm')
                ->name('register.otp.form');

            Route::post('/verify', 'verifyOtp')
                ->name('register.otp.verify');

            Route::post('/resend-otp', 'resendOtp')
                ->middleware('throttle:3,1')
                ->name('register.resend-otp');
        });
});

Route::view('/registration/pending', 'auth.registration-pending')
    ->middleware('auth')
    ->name('registration.pending');

/*
|--------------------------------------------------------------------------
| Help / Support
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
| Return Evidence Media
|--------------------------------------------------------------------------
|
| The controller must authorize access to the media through the associated
| return request and its buyer/seller ownership rules.
|
*/

Route::middleware('auth')
    ->get('/returns/media/{returnMedia}', [
        ReturnMediaController::class,
        'show',
    ])->name('returns.media');

/*
|--------------------------------------------------------------------------
| Messaging
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('messages')
    ->name('messages.')
    ->controller(MessagingController::class)
    ->group(function () {

        // Inbox and lookups
        Route::get('/', 'index')->name('index');
        Route::get('/contacts', 'contacts')->name('contacts');
        Route::get('/recent-purchases', 'recentPurchases')
            ->name('recent-purchases');
        Route::get('/widget-data', 'widgetData')->name('widget-data');
        Route::get('/unread-count', 'unreadCount')->name('unread-count');
        Route::get('/support', 'openSupport')->name('support');

        // Attachments
        Route::get('/attachments/{attachment}', 'attachment')
            ->name('attachment');

        // Create and open conversations
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
| Notifications (any authenticated user)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('notifications')
    ->name('notifications.')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/read-all', 'readAll')->name('read-all');
        Route::post('/{id}/read', 'read')->name('read');
    });


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Registration management
        $registrations = [
            'sellers' => [
                SellerManagementController::class,
                'sellerProfile',
            ],
            'buyers' => [
                BuyerManagementController::class,
                'buyerProfile',
            ],
            'logistics' => [
                LogisticsManagementController::class,
                'logisticsProfile',
            ],
        ];

        Route::prefix('registrations')
            ->name('registrations.')
            ->group(function () use ($registrations) {
                foreach ($registrations as $type => [$controller, $param]) {
                    Route::prefix($type)
                        ->name("{$type}.")
                        ->controller($controller)
                        ->group(function () use ($param) {
                            Route::get('/', 'index')->name('index');

                            Route::get("/{{$param}}/details", 'details')
                                ->name('details');

                            Route::post("/{{$param}}/approve", 'approve')
                                ->name('approve');

                            Route::post("/{{$param}}/reject", 'reject')
                                ->name('reject');

                            Route::post(
                                "/{{$param}}/account-status",
                                'updateAccountStatus'
                            )->name('accountStatus');
                        });
                }
            });

        // Transactions
        Route::prefix('transactions')
            ->name('transactions.')
            ->controller(TransactionController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/csv', 'exportCsv')
                    ->name('export.csv');
            });

        // Returns & refunds (disputes and refund payouts)
        Route::prefix('returns')->name('returns.')->controller(AdminReturnController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{orderReturn}', 'show')->name('show');
            Route::post('/{orderReturn}/decide', 'decide')->name('decide');
            Route::post('/{orderReturn}/refunded', 'markRefunded')->name('refunded');
        });

        // Activity logs
        Route::get('/logs', [ActivityLogController::class, 'index'])
            ->name('logs.index');

        // Platform settings
        Route::prefix('settings')
            ->name('settings.')
            ->controller(PlatformSettingController::class)
            ->group(function () {

                // General
                Route::get('/', 'index')->name('index');
                Route::post('/general', 'updateGeneral')->name('general');

                // Shipping
                Route::get('/shipping', 'shipping')
                    ->name('shipping.index');

                Route::post('/shipping', 'updateShipping')
                    ->name('shipping');

                // Announcements
                Route::prefix('announcements')
                    ->name('announcements.')
                    ->group(function () {
                        Route::get('/', 'announcements')->name('index');

                        Route::post('/', 'storeAnnouncement')
                            ->name('store');

                        Route::post(
                            '/{announcement}',
                            'updateAnnouncement'
                        )->name('update');

                        Route::post(
                            '/{announcement}/toggle',
                            'toggleAnnouncement'
                        )->name('toggle');

                        Route::delete(
                            '/{announcement}',
                            'destroyAnnouncement'
                        )->name('destroy');
                    });

                // Policies
                Route::prefix('policies')
                    ->name('policies.')
                    ->group(function () {
                        Route::get('/', 'policies')->name('index');

                        Route::post('/{policy}', 'updatePolicy')
                            ->name('update');
                    });
            });
    });

/*
|--------------------------------------------------------------------------
| Logistics / Sorting Center Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'logistics'])
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function () {

        Route::get('/dashboard', [
            LogisticsDashboardController::class,
            'index',
        ])->name('dashboard');

        // Riders
        Route::prefix('riders')
            ->name('riders.')
            ->controller(RiderController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{rider}/approve', 'approve')->name('approve');
                Route::post('/{rider}/reject', 'reject')->name('reject');

                Route::post('/{rider}/toggle-active', 'toggleActive')
                    ->name('toggle-active');
            });

        // Pickups
        Route::prefix('pickups')
            ->name('pickups.')
            ->controller(PickupController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/{order}/confirm', 'confirmPickup')
                    ->name('confirm');

                Route::post('/{order}/assign', 'assign')
                    ->name('assign');

                Route::post('/{order}/decline', 'decline')
                    ->name('decline');
            });

        // Incoming parcels
        Route::prefix('incoming')
            ->name('incoming.')
            ->controller(IncomingParcelController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/{order}/confirm-arrival', 'confirmArrival')
                    ->name('confirm-arrival');
            });

        // Service areas
        Route::prefix('areas')
            ->name('areas.')
            ->controller(ServiceAreaController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::post('/{area}', 'update')->name('update');

                Route::post('/{area}/barangays', 'updateBarangays')
                    ->name('barangays');

                Route::delete('/{area}', 'destroy')->name('destroy');
            });

        // Sorting
        Route::prefix('sorting')
            ->name('sorting.')
            ->controller(SortingController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/sort-all', 'sortAll')->name('sort-all');
                Route::post('/assign-area', 'assignArea')
                    ->name('assign-area');

                Route::post('/{order}/sort', 'sort')->name('sort');
                Route::post('/{order}/assign', 'assign')->name('assign');
            });

        // Deliveries
        Route::prefix('deliveries')
            ->name('deliveries.')
            ->controller(DeliveryController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/{order}/out', 'outForDelivery')
                    ->name('out');

                Route::post('/{order}/delivered', 'delivered')
                    ->name('delivered');

                Route::post('/{order}/failed', 'failed')
                    ->name('failed');

                Route::post('/{order}/reschedule', 'reschedule')
                    ->name('reschedule');

                Route::post('/{order}/return', 'returnToSeller')
                    ->name('return');
            });
    });

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'seller'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {

        Route::get('/dashboard', [SellerDashboardController::class, 'index'])
            ->name('dashboard');

        // Products
        Route::prefix('products')
            ->name('products.')
            ->controller(SellerProductController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('/{product}/details', 'details')
                    ->name('details');

                Route::post('/{product}', 'update')->name('update');

                Route::post('/{product}/status', 'updateStatus')
                    ->name('status');

                Route::get('/{product}/discount', [
                    SellerProductDiscountController::class,
                    'edit',
                ])->name('discount.edit');

                Route::post('/{product}/discount', [
                    SellerProductDiscountController::class,
                    'update',
                ])->name('discount.update');

                Route::delete('/{product}/discount', [
                    SellerProductDiscountController::class,
                    'destroy',
                ])->name('discount.destroy');
            });

        // Orders
        Route::prefix('orders')
            ->name('orders.')
            ->group(function () {

                Route::controller(SellerOrderController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{order}', 'show')->name('show');

                    Route::post('/{order}/status', 'updateStatus')
                        ->name('status');

                    Route::post('/{order}/request-pickup', 'requestPickup')
                        ->name('request-pickup');

                    Route::post('/{order}/cancel-pickup', 'cancelPickup')
                        ->name('cancel-pickup');

                    // Cancellation
                    Route::post('/{order}/cancel', 'cancel')
                        ->middleware('throttle:20,1')
                        ->name('cancel');

                    Route::post(
                        '/{order}/cancel-request/approve',
                        'approveCancel'
                    )->name('cancel-request.approve');

                    Route::post(
                        '/{order}/cancel-request/decline',
                        'declineCancel'
                    )->name('cancel-request.decline');
                });

                Route::get('/{order}/waybill', [
                    WaybillController::class,
                    'show',
                ])->name('waybill');
            });

        // Reports
        Route::prefix('reports')
            ->name('reports.')
            ->controller(SellerReportController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::get('/export/csv', 'exportCsv')
                    ->name('export.csv');

                Route::get('/export/pdf', 'exportPdf')
                    ->name('export.pdf');
            });

        // Reviews
        Route::prefix('reviews')
            ->name('reviews.')
            ->controller(SellerReviewController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/{review}/reply', 'reply')
                    ->middleware('throttle:30,1')
                    ->name('reply');

                Route::delete('/{review}/reply', 'destroyReply')
                    ->name('reply.destroy');
            });

        // Returns & refunds
        Route::prefix('returns')
            ->name('returns.')
            ->controller(SellerReturnController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{orderReturn}', 'show')->name('show');

                Route::post('/{orderReturn}/approve', 'approve')
                    ->name('approve');

                Route::post('/{orderReturn}/decline', 'decline')
                    ->name('decline');

                Route::post('/{orderReturn}/received', 'received')
                    ->name('received');

                Route::post('/{orderReturn}/problem', 'problem')
                    ->name('problem');
            });

        // Account
        Route::prefix('account')
            ->name('account.')
            ->controller(SellerAccountController::class)
            ->group(function () {
                Route::get('/', 'show')->name('index');
                Route::post('/profile', 'updateProfile')->name('profile');
                Route::post('/address', 'updateAddress')->name('address');
                Route::post('/password', 'updatePassword')->name('password');
                Route::post('/avatar', 'updateAvatar')->name('avatar');
                Route::delete('/avatar', 'destroyAvatar')
                    ->name('avatar.destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| Buyer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'buyer'])
    ->prefix('buyer')
    ->name('buyer.')
    ->group(function () {

        Route::get('/', [BuyerHomeController::class, 'index'])
            ->name('home');

        // Browsing
        Route::get('/products', [BuyerProductController::class, 'index'])
            ->name('products.index');

        Route::get('/products/{product}', [
            BuyerProductController::class,
            'show',
        ])->name('products.show');

        Route::get('/shop/{seller}', [ShopController::class, 'show'])
            ->name('shop.show');

        // Cart
        Route::controller(CartController::class)
            ->name('cart.')
            ->group(function () {
                Route::get('/cart', 'index')->name('index');
                Route::post('/cart/{product}', 'store')->name('store');

                Route::post('/cart/item/{cartItem}', 'update')
                    ->name('update');

                Route::delete('/cart/item/{cartItem}', 'destroy')
                    ->name('destroy');

                Route::delete('/cart', 'destroyMany')
                    ->name('destroyMany');
            });

        // Checkout
        Route::controller(CheckoutController::class)
            ->prefix('checkout')
            ->name('checkout.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
            });

        // Orders
        Route::prefix('orders')
            ->name('orders.')
            ->group(function () {

                Route::controller(BuyerOrderController::class)->group(function () {
                    Route::get('/', 'index')->name('index');

                    Route::post('/{order}/confirm', 'confirmReceived')
                        ->name('confirm');

                    Route::post('/{order}/buy-again', 'buyAgain')
                        ->name('buy-again');

                    // Cancellation
                    Route::post('/{order}/cancel', 'cancel')
                        ->middleware('throttle:20,1')
                        ->name('cancel');

                    Route::post(
                        '/{order}/cancel-request/withdraw',
                        'withdrawCancelRequest'
                    )->name('cancel-request.withdraw');
                });

                // Request a return or refund for an order
                Route::get('/{order}/return', [
                    BuyerReturnController::class,
                    'create',
                ])->name('return.create');

                Route::post('/{order}/return', [
                    BuyerReturnController::class,
                    'store',
                ])
                    ->middleware('throttle:10,1')
                    ->name('return.store');

                // Review a purchased item
                Route::post(
                    '/{order}/items/{item}/review',
                    [BuyerReviewController::class, 'store']
                )
                    ->middleware('throttle:20,1')
                    ->name('review.store');
            });

        // Track and manage return/refund requests
        Route::prefix('returns')
            ->name('returns.')
            ->controller(BuyerReturnController::class)
            ->group(function () {
                Route::get('/{orderReturn}', 'show')->name('show');

                Route::post('/{orderReturn}/withdraw', 'withdraw')
                    ->name('withdraw');

                Route::post('/{orderReturn}/ship', 'ship')
                    ->name('ship');

                Route::post('/{orderReturn}/escalate', 'escalate')
                    ->name('escalate');
            });

        // Account
        Route::prefix('account')
            ->name('account.')
            ->controller(BuyerAccountController::class)
            ->group(function () {
                Route::get('/', 'show')->name('index');
                Route::post('/profile', 'updateProfile')->name('profile');
                Route::post('/address', 'updateAddress')->name('address');
                Route::post('/password', 'updatePassword')->name('password');
                Route::post('/avatar', 'updateAvatar')->name('avatar');
                Route::delete('/avatar', 'destroyAvatar')
                    ->name('avatar.destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Automatically complete delivered orders.
Schedule::command('orders:auto-complete')->dailyAt('02:00');

// Automatically approve eligible pending cancellation requests after 24 hours.
Schedule::command('orders:auto-approve-cancellations')->hourly();

// Process return/refund deadlines and eligible automatic actions.
Schedule::command('returns:process-deadlines')->hourly();
