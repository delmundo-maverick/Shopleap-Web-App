<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BuyerManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HubAdminController;
use App\Http\Controllers\Admin\LogisticsManagementController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Auth\BuyerRegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogisticsRegistrationController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Buyer\AccountController as BuyerAccountController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\CheckoutController;
use App\Http\Controllers\Buyer\HomeController as BuyerHomeController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ProductController as BuyerProductController;
use App\Http\Controllers\Buyer\ReturnController as BuyerReturnController;
use App\Http\Controllers\Buyer\ReviewController as BuyerReviewController;
use App\Http\Controllers\Buyer\SellerFollowController;
use App\Http\Controllers\Buyer\ShopController;
use App\Http\Controllers\Buyer\VoucherController as BuyerVoucherController;
use App\Http\Controllers\Buyer\WishlistController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Logistics\DeliveryController;
use App\Http\Controllers\Logistics\InboundController;
use App\Http\Controllers\Logistics\IncomingParcelController;
use App\Http\Controllers\Logistics\OutboundController;
use App\Http\Controllers\Logistics\PickupController;
use App\Http\Controllers\Logistics\ReportController as LogisticsReportController;
use App\Http\Controllers\Logistics\RiderController;
use App\Http\Controllers\Logistics\ServiceAreaController;
use App\Http\Controllers\Logistics\SortingController;
use App\Http\Controllers\Logistics\SortingSessionController as LogisticsSortingSessionController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ReturnMediaController;
use App\Http\Controllers\Seller\AccountController as SellerAccountController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\ProductDiscountController as SellerProductDiscountController;
use App\Http\Controllers\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Seller\ReturnController as SellerReturnController;
use App\Http\Controllers\Seller\ReviewController as SellerReviewController;
use App\Http\Controllers\Seller\ShopVoucherController;
use App\Http\Controllers\Seller\WaybillController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Public
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

    // OTP verification (shared by all three roles)
    Route::prefix('{role}')
        ->where(['role' => 'buyer|seller|logistics'])
        ->controller(LoginController::class)
        ->group(function () {
            Route::get('/verify', 'showOtpForm')->name('register.otp.form');
            Route::post('/verify', 'verifyOtp')->name('register.otp.verify');

            Route::post('/resend-otp', 'resendOtp')
                ->middleware('throttle:3,1')
                ->name('register.resend-otp');
        });
});

/*
|--------------------------------------------------------------------------
| Shared (any authenticated user)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::view('/registration/pending', 'auth.registration-pending')
        ->name('registration.pending');

    // Help / Support
    Route::prefix('help')
        ->name('help.')
        ->controller(SupportController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');

            Route::post('/tickets', 'store')
                ->middleware('throttle:10,1')
                ->name('tickets.store');
        });

    // Return evidence media.
    // The controller must authorize access through the associated return
    // request and its buyer/seller ownership rules.
    Route::get('/returns/media/{returnMedia}', [ReturnMediaController::class, 'show'])
        ->name('returns.media');

    // Messaging
    Route::prefix('messages')
        ->name('messages.')
        ->controller(MessagingController::class)
        ->group(function () {

            // Inbox and lookups
            Route::get('/', 'index')->name('index');
            Route::get('/contacts', 'contacts')->name('contacts');
            Route::get('/recent-purchases', 'recentPurchases')->name('recent-purchases');
            Route::get('/widget-data', 'widgetData')->name('widget-data');
            Route::get('/unread-count', 'unreadCount')->name('unread-count');
            Route::get('/support', 'openSupport')->name('support');

            // Attachments
            Route::get('/attachments/{attachment}', 'attachment')->name('attachment');

            // Create / open / forward
            Route::post('/', 'store')->name('store');
            Route::post('/open', 'open')->name('open');
            Route::post('/forward/{message}', 'forward')->name('forward');

            // Single conversation (keep after the literal routes above)
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

    // Notifications
    Route::prefix('notifications')
        ->name('notifications.')
        ->controller(NotificationController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/read-all', 'readAll')->name('read-all');

            Route::get('/feed', 'feed')
                ->middleware('throttle:120,1')
                ->name('feed');

            Route::post('/{id}/read', 'read')->name('read');
        });
});

/*
|--------------------------------------------------------------------------
| Buyer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'buyer'])
    ->prefix('buyer')
    ->name('buyer.')
    ->group(function () {

        Route::get('/', [BuyerHomeController::class, 'index'])->name('home');

        // Browsing
        Route::prefix('products')
            ->name('products.')
            ->controller(BuyerProductController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{product}', 'show')->name('show');
            });

        Route::get('/shop/{seller}', [ShopController::class, 'show'])
            ->name('shop.show');

        // Follow / unfollow a seller.
        Route::post('/sellers/{seller}/follow', [SellerFollowController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('sellers.follow');

        Route::delete('/sellers/{seller}/follow', [SellerFollowController::class, 'destroy'])
            ->middleware('throttle:60,1')
            ->name('sellers.unfollow');

        // Cart
        Route::prefix('cart')
            ->name('cart.')
            ->controller(CartController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::delete('/', 'destroyMany')->name('destroyMany');
                Route::post('/{product}', 'store')->name('store');
                Route::post('/item/{cartItem}', 'update')->name('update');
                Route::delete('/item/{cartItem}', 'destroy')->name('destroy');
            });

        // Wishlist
        Route::prefix('wishlist')
            ->name('wishlist.')
            ->controller(WishlistController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');

                Route::post('/{product}', 'store')
                    ->middleware('throttle:60,1')
                    ->name('store');

                Route::delete('/{product}', 'destroy')
                    ->middleware('throttle:60,1')
                    ->name('destroy');
            });

        // Vouchers
        Route::prefix('vouchers')
            ->name('vouchers.')
            ->controller(BuyerVoucherController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/dropdown', 'dropdown')->name('dropdown');
                Route::post('/apply', 'apply')->name('apply');
            });

        // Checkout
        Route::prefix('checkout')
            ->name('checkout.')
            ->controller(CheckoutController::class)
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
                    Route::post('/{order}/confirm', 'confirmReceived')->name('confirm');
                    Route::post('/{order}/buy-again', 'buyAgain')->name('buy-again');

                    // Cancellation
                    Route::post('/{order}/cancel', 'cancel')
                        ->middleware('throttle:20,1')
                        ->name('cancel');

                    Route::post('/{order}/cancel-request/withdraw', 'withdrawCancelRequest')
                        ->name('cancel-request.withdraw');
                });

                // Request a return or refund for an order
                Route::get('/{order}/return', [BuyerReturnController::class, 'create'])
                    ->name('return.create');

                Route::post('/{order}/return', [BuyerReturnController::class, 'store'])
                    ->middleware('throttle:10,1')
                    ->name('return.store');

                // Review a purchased item
                Route::post('/{order}/items/{item}/review', [BuyerReviewController::class, 'store'])
                    ->middleware('throttle:20,1')
                    ->name('review.store');
            });

        // Track and manage return/refund requests
        Route::prefix('returns')
            ->name('returns.')
            ->controller(BuyerReturnController::class)
            ->group(function () {
                Route::get('/{orderReturn}', 'show')->name('show');
                Route::post('/{orderReturn}/withdraw', 'withdraw')->name('withdraw');
                Route::post('/{orderReturn}/ship', 'ship')->name('ship');
                Route::post('/{orderReturn}/escalate', 'escalate')->name('escalate');
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
                Route::delete('/avatar', 'destroyAvatar')->name('avatar.destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| Seller
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
            ->group(function () {

                Route::controller(SellerProductController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{product}/details', 'details')->name('details');
                    Route::post('/{product}', 'update')->name('update');
                    Route::post('/{product}/status', 'updateStatus')->name('status');

                    // General product images (max 5)
                    Route::post('/{product}/images', 'storeImage')->name('images.store');
                    Route::post('/{product}/images/{image}', 'updateImage')->name('images.update');
                    Route::delete('/{product}/images/{image}', 'destroyImage')->name('images.destroy');

                    // Optional image per variant
                    Route::post('/{product}/variants/{variant}/image', 'storeVariantImage')
                        ->name('variants.image');

                    Route::delete('/{product}/variants/{variant}/image', 'destroyVariantImage')
                        ->name('variants.image.destroy');
                });

                // Discounts
                Route::controller(SellerProductDiscountController::class)->group(function () {
                    Route::get('/{product}/discount', 'edit')->name('discount.edit');
                    Route::post('/{product}/discount', 'update')->name('discount.update');
                    Route::delete('/{product}/discount', 'destroy')->name('discount.destroy');
                });
            });

        // Orders
        Route::prefix('orders')
            ->name('orders.')
            ->group(function () {

                Route::controller(SellerOrderController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{order}', 'show')->name('show');
                    Route::post('/{order}/status', 'updateStatus')->name('status');

                    // Pickup
                    Route::post('/{order}/request-pickup', 'requestPickup')->name('request-pickup');
                    Route::post('/{order}/cancel-pickup', 'cancelPickup')->name('cancel-pickup');

                    // Cancellation
                    Route::post('/{order}/cancel', 'cancel')
                        ->middleware('throttle:20,1')
                        ->name('cancel');

                    Route::post('/{order}/cancel-request/approve', 'approveCancel')
                        ->name('cancel-request.approve');

                    Route::post('/{order}/cancel-request/decline', 'declineCancel')
                        ->name('cancel-request.decline');
                });

                Route::get('/{order}/waybill', [WaybillController::class, 'show'])
                    ->name('waybill');
            });

        // Reports
        Route::prefix('reports')
            ->name('reports.')
            ->controller(SellerReportController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export/csv', 'exportCsv')->name('export.csv');
                Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
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

                Route::delete('/{review}/reply', 'destroyReply')->name('reply.destroy');
            });

        // Returns & refunds
        Route::prefix('returns')
            ->name('returns.')
            ->controller(SellerReturnController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{orderReturn}', 'show')->name('show');
                Route::post('/{orderReturn}/approve', 'approve')->name('approve');
                Route::post('/{orderReturn}/decline', 'decline')->name('decline');
                Route::post('/{orderReturn}/received', 'received')->name('received');
                Route::post('/{orderReturn}/problem', 'problem')->name('problem');
            });

        // Shop vouchers
        Route::prefix('vouchers')
            ->name('vouchers.')
            ->controller(ShopVoucherController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/generate-code', 'generateCode')->name('generate-code');

                Route::get('/{shopVoucher}', 'show')->name('show');
                Route::get('/{shopVoucher}/edit', 'edit')->name('edit');
                Route::match(['put', 'patch'], '/{shopVoucher}', 'update')->name('update');
                Route::post('/{shopVoucher}/toggle', 'toggle')->name('toggle');
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
                Route::delete('/avatar', 'destroyAvatar')->name('avatar.destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| Logistics / Sorting Center
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'logistics', 'hub.scope'])
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function () {

        Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])
            ->name('dashboard');

        // Riders
        Route::prefix('riders')
            ->name('riders.')
            ->controller(RiderController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{rider}/approve', 'approve')->name('approve');
                Route::post('/{rider}/reject', 'reject')->name('reject');
                Route::post('/{rider}/toggle-active', 'toggleActive')->name('toggle-active');
            });

        // Pickups
        Route::prefix('pickups')
            ->name('pickups.')
            ->controller(PickupController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{order}/confirm', 'confirmPickup')->name('confirm');
                Route::post('/{order}/assign', 'assign')->name('assign');
                Route::post('/{order}/decline', 'decline')->name('decline');
            });

        // Incoming parcels
        Route::prefix('incoming')
            ->name('incoming.')
            ->controller(IncomingParcelController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{order}/confirm-arrival', 'confirmArrival')->name('confirm-arrival');
            });

        // Service areas
        Route::prefix('areas')
            ->name('areas.')
            ->controller(ServiceAreaController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::delete('/{area}', 'destroy')->name('destroy');
            });

        // Sorting
        Route::prefix('sorting')
            ->name('sorting.')
            ->group(function () {

                // Sessions (declared before the {order} wildcard routes below)
                Route::prefix('sessions')
                    ->name('sessions.')
                    ->controller(LogisticsSortingSessionController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/start', 'start')->name('start');

                        Route::post('/{session}/scan', 'scan')
                            ->middleware('throttle:120,1')
                            ->name('scan');

                        Route::post('/{session}/end', 'end')->name('end');
                        Route::get('/{session}/summary', 'summary')->name('summary');
                    });

                Route::controller(SortingController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/sort-all', 'sortAll')->name('sort-all');
                    Route::post('/assign-area', 'assignArea')->name('assign-area');
                    Route::post('/{order}/sort', 'sort')->name('sort');
                    Route::post('/{order}/assign', 'assign')->name('assign');
                });
            });

        // Deliveries
        Route::prefix('deliveries')
            ->name('deliveries.')
            ->controller(DeliveryController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/{order}/out', 'outForDelivery')->name('out');
                Route::post('/{order}/delivered', 'delivered')->name('delivered');
                Route::post('/{order}/failed', 'failed')->name('failed');
                Route::post('/{order}/reschedule', 'reschedule')->name('reschedule');
                Route::post('/{order}/return', 'returnToSeller')->name('return');
            });

        // Outbound hub-to-hub transfer
        Route::prefix('outbound')
            ->name('outbound.')
            ->controller(OutboundController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/bags', 'store')->name('bags.store');
                Route::get('/bags/{bag}', 'show')->name('show');
                Route::post('/bags/{bag}/orders', 'addOrders')->name('orders.store');
                Route::post('/bags/{bag}/scan', 'scan')
                    ->middleware('throttle:120,1')
                    ->name('scan');
                Route::delete('/bags/{bag}/orders/{order}', 'removeOrder')->name('orders.destroy');
                Route::post('/bags/{bag}/seal', 'seal')->name('seal');
                Route::post('/bags/{bag}/dispatch', 'dispatch')->name('dispatch');
                Route::get('/bags/{bag}/manifest', 'manifest')->name('manifest');
            });

        // Inbound hub-to-hub transfer
        Route::prefix('inbound')
            ->name('inbound.')
            ->controller(InboundController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/bags/{bag}', 'show')->name('show');
                Route::post('/bags/{bag}/verify', 'verify')
                    ->middleware('throttle:120,1')
                    ->name('verify');
                Route::post('/bags/{bag}/scan', 'scan')
                    ->middleware('throttle:120,1')
                    ->name('scan');
                Route::post('/bags/{bag}/parcels/{order}', 'confirmParcel')->name('parcels.confirm');
                Route::post('/bags/{bag}/close', 'close')->name('close');
            });

        // Reports
        Route::prefix('reports')
            ->name('reports.')
            ->controller(LogisticsReportController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export/csv', 'exportCsv')->name('export.csv');
                Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
            });

    });

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Registration management: type => [controller, route parameter]
        $registrations = [
            'sellers' => [SellerManagementController::class, 'sellerProfile'],
            'buyers' => [BuyerManagementController::class, 'buyerProfile'],
            'logistics' => [LogisticsManagementController::class, 'logisticsProfile'],
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
                            Route::get("/{{$param}}/details", 'details')->name('details');
                            Route::post("/{{$param}}/approve", 'approve')->name('approve');
                            Route::post("/{{$param}}/reject", 'reject')->name('reject');
                            Route::post("/{{$param}}/account-status", 'updateAccountStatus')
                                ->name('accountStatus');
                        });
                }
            });

        // Transactions
        Route::prefix('transactions')
            ->name('transactions.')
            ->controller(TransactionController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/export/csv', 'exportCsv')->name('export.csv');
            });

        // Returns & refunds (disputes and refund payouts)
        Route::prefix('returns')
            ->name('returns.')
            ->controller(AdminReturnController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{orderReturn}', 'show')->name('show');
                Route::post('/{orderReturn}/decide', 'decide')->name('decide');
                Route::post('/{orderReturn}/refunded', 'markRefunded')->name('refunded');
            });

        // Hub & company administration (super admin)
        Route::prefix('hubs')
            ->name('hubs.')
            ->controller(HubAdminController::class)
            ->group(function () {
                Route::get('/', 'companies')->name('companies');
                Route::get('/barangays', 'barangays')->name('barangays');
                Route::get('/transfers', 'transfers')->name('transfers');
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
                Route::get('/shipping', 'shipping')->name('shipping.index');
                Route::post('/shipping', 'updateShipping')->name('shipping');

                // Announcements
                Route::prefix('announcements')
                    ->name('announcements.')
                    ->group(function () {
                        Route::get('/', 'announcements')->name('index');
                        Route::post('/', 'storeAnnouncement')->name('store');
                        Route::post('/{announcement}', 'updateAnnouncement')->name('update');
                        Route::post('/{announcement}/toggle', 'toggleAnnouncement')->name('toggle');
                        Route::delete('/{announcement}', 'destroyAnnouncement')->name('destroy');
                    });

                // Policies
                Route::prefix('policies')
                    ->name('policies.')
                    ->group(function () {
                        Route::get('/', 'policies')->name('index');
                        Route::post('/{policy}', 'updatePolicy')->name('update');
                    });
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

// Close stale sorting sessions after 12 hours of inactivity.
Schedule::command('sorting:close-stale-sessions')->hourly();

// Flag bags that stay in transit too long and parcels with no tracking updates.
Schedule::command('bags:flag-stale-in-transit')->hourly();
Schedule::command('parcels:flag-stale-events')->hourly();
