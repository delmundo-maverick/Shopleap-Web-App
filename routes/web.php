<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BuyerManagementController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HubAdminController;
use App\Http\Controllers\Admin\LogisticsManagementController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\ReturnController as AdminReturnController;
use App\Http\Controllers\Admin\SellerManagementController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Auth\BuyerRegistrationController;
use App\Http\Controllers\Auth\CompanyRegistrationController;
use App\Http\Controllers\Auth\HubInvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ManagerPasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SellerRegistrationController;
use App\Http\Controllers\Buyer\AccountController as BuyerAccountController;
use App\Http\Controllers\Buyer\AddressController;
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
use App\Http\Controllers\Company\AnnouncementController;
use App\Http\Controllers\Company\CoverageController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\FinanceController;
use App\Http\Controllers\Company\HubController;
use App\Http\Controllers\Company\HubMapController;
use App\Http\Controllers\Company\ManagerController;
use App\Http\Controllers\Company\ReportController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\TransferController;
use App\Http\Controllers\DeliveryProofController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Logistics\CompanyAnnouncementController;
use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Logistics\DeliveryController;
use App\Http\Controllers\Logistics\InboundController;
use App\Http\Controllers\Logistics\IncomingParcelController;
use App\Http\Controllers\Logistics\OutboundController;
use App\Http\Controllers\Logistics\PickupController;
use App\Http\Controllers\Logistics\PortalController;
use App\Http\Controllers\Logistics\ReportController as LogisticsReportController;
use App\Http\Controllers\Logistics\RiderController;
use App\Http\Controllers\Logistics\RiderRateController;
use App\Http\Controllers\Logistics\ServiceAreaController;
use App\Http\Controllers\Logistics\SortingController;
use App\Http\Controllers\Logistics\SortingSessionController as LogisticsSortingSessionController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\MoneyController;
use App\Http\Controllers\MoneyMovementController;
use App\Http\Controllers\MoneyReceiptController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayoutController;
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
use App\Http\Controllers\SellerSettlementController;
use App\Http\Controllers\SupportController;
use App\Models\Company;
use App\Services\MoneyReceipts;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/money/receipts/{type}/{reference}', [MoneyReceiptController::class, 'show'])
    ->middleware('auth')->whereIn('type', MoneyReceipts::TYPES)->name('money.receipts.show');

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::domain(Portal::host('logistics'))->group(function (): void {
    Route::get('/company/register/choose', [CompanyRegistrationController::class, 'choose'])->name('company.register.choose');
    Route::get('/company/register', [CompanyRegistrationController::class, 'create'])->name('company.register');
    Route::get('/company/register/locations', [CompanyRegistrationController::class, 'locations'])->middleware('throttle:60,1')->name('company.register.locations');
    Route::post('/company/register', [CompanyRegistrationController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/company/manager-password/{hub:public_code}', [ManagerPasswordController::class, 'show'])->middleware('signed:relative')->whereUuid('hub')->name('company.manager-password.show');
    Route::post('/company/manager-password/{hub:public_code}', [ManagerPasswordController::class, 'store'])->middleware(['signed:relative', 'throttle:10,1'])->whereUuid('hub')->name('company.manager-password.store');
    Route::get('/company/invitation/{hub:public_code}', [HubInvitationController::class, 'show'])->middleware('signed:relative')->whereUuid('hub')->name('company.invitation.show');
    Route::post('/company/invitation/{hub:public_code}', [HubInvitationController::class, 'store'])->middleware(['signed:relative', 'throttle:10,1'])->whereUuid('hub')->name('company.invitation.store');
    Route::middleware(['auth', 'logistics_company', 'throttle:mutations'])->prefix('company')->name('company.')->group(function (): void {
        Route::get('/status', [DashboardController::class, 'status'])->name('status');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/attention', [DashboardController::class, 'attention'])->name('attention');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
        Route::get('/activity-log', [App\Http\Controllers\Company\ActivityLogController::class, 'index'])->name('activity-log');
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('throttle:10,1')->name('announcements.store');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/password', [SettingsController::class, 'password'])->middleware('throttle:5,1')->name('settings.password');
        Route::get('/coverage', [CoverageController::class, 'index'])->name('coverage');
        Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/{bag:code}', [TransferController::class, 'show'])->name('transfers.show');
        Route::get('/map', [HubMapController::class, 'index'])->name('map');
        Route::get('/managers', [ManagerController::class, 'index'])->name('managers.index');
        Route::put('/managers/{hub:public_code}', [ManagerController::class, 'update'])->whereUuid('hub')->name('managers.update');
        Route::post('/managers/{hub:public_code}/suspend', [ManagerController::class, 'suspend'])->whereUuid('hub')->name('managers.suspend');
        Route::post('/managers/{hub:public_code}/reactivate', [ManagerController::class, 'reactivate'])->whereUuid('hub')->name('managers.reactivate');
        Route::post('/managers/{hub:public_code}/reset-password', [ManagerController::class, 'resetPassword'])->middleware('throttle:3,1')->whereUuid('hub')->name('managers.reset-password');
        Route::post('/managers/{hub:public_code}/invite', [ManagerController::class, 'invite'])->middleware('throttle:3,1')->whereUuid('hub')->name('managers.invite');
        Route::post('/managers/{hub:public_code}/logout', [ManagerController::class, 'forceLogout'])->whereUuid('hub')->name('managers.logout');
        Route::get('/locations', [HubController::class, 'locations'])->name('locations');
        Route::get('/barangays', [HubController::class, 'barangays'])->name('barangays');
        Route::get('/hubs', [HubController::class, 'index'])->name('hubs.index');
        Route::get('/hubs/create', [HubController::class, 'create'])->name('hubs.create');
        Route::post('/hubs', [HubController::class, 'store'])->name('hubs.store');
        Route::get('/hubs/{hub:public_code}/edit', [HubController::class, 'edit'])->whereUuid('hub')->name('hubs.edit');
        Route::put('/hubs/{hub:public_code}', [HubController::class, 'update'])->whereUuid('hub')->name('hubs.update');
        Route::post('/hubs/{hub:public_code}/suspend', [HubController::class, 'suspend'])->whereUuid('hub')->name('hubs.suspend');
        Route::post('/hubs/{hub:public_code}/reactivate', [HubController::class, 'reactivate'])->whereUuid('hub')->name('hubs.reactivate');
        Route::post('/hubs/{hub:public_code}/invite', [HubController::class, 'invite'])->middleware('throttle:3,1')->whereUuid('hub')->name('hubs.invite');
    });
    Route::get('/register', [CompanyRegistrationController::class, 'create'])->name('register.logistics');
    Route::post('/register', [CompanyRegistrationController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/logistics/application', [PortalController::class, 'application'])
        ->middleware('auth')->name('logistics.application');
    Route::post('/logistics/application', [PortalController::class, 'resubmit'])
        ->middleware(['auth', 'throttle:5,1'])->name('logistics.application.resubmit');
});

Route::get('/terms', [PolicyController::class, 'terms'])->name('terms');
Route::get('/terms/content', [PolicyController::class, 'termsContent'])->name('terms.content');

Route::get('/privacy', [PolicyController::class, 'privacy'])->name('privacy');
Route::get('/privacy/content', [PolicyController::class, 'privacyContent'])->name('privacy.content');

Route::get('/policies/{policy}', [PolicyController::class, 'show'])
    ->name('policies.show');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->prefix('forgot-password')->group(function () {
    Route::get('/', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/', [PasswordResetController::class, 'send'])->middleware('throttle:20,60')->name('password.email');
    Route::get('/verify', [PasswordResetController::class, 'verifyForm'])->name('password.verify');
    Route::post('/verify', [PasswordResetController::class, 'verify'])->middleware('throttle:20,1')->name('password.verify.store');
    Route::post('/resend', [PasswordResetController::class, 'resend'])->middleware('throttle:20,60')->name('password.resend');
    Route::get('/reset', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')->middleware('throttle:10,1');
    Route::post('/logout', 'destroy')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

Route::get('/register', function (): RedirectResponse {
    return redirect()->route('register.buyer');
})->name('register');

Route::prefix('register')->group(function () {

    Route::controller(BuyerRegistrationController::class)->group(function () {
        Route::get('/buyer', 'create')->name('register.buyer');
        Route::post('/buyer', 'store')->middleware('throttle:10,1');
    });

    Route::controller(SellerRegistrationController::class)->group(function () {
        Route::get('/seller', 'create')->name('register.seller');
        Route::post('/seller', 'store')->middleware('throttle:10,1');
    });

    // OTP verification (shared by all three roles)
    Route::prefix('{role}')
        ->where(['role' => 'buyer|seller|logistics|logistics_company'])
        ->controller(LoginController::class)
        ->group(function () {
            Route::get('/verify', 'showOtpForm')->name('register.otp.form');
            Route::post('/verify', 'verifyOtp')->middleware('throttle:10,1')->name('register.otp.verify');

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

Route::middleware(['auth', 'throttle:mutations'])->group(function () {

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
        ->middleware('throttle:messaging')
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

Route::middleware(['auth', 'buyer', 'throttle:mutations'])
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
                Route::get('/{product}/stock', 'stock')->middleware('throttle:30,1')->name('stock');
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
                Route::post('/{shopVoucher}/claim', 'claim')->middleware('throttle:30,1')->name('claim');
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

        Route::get('/addresses/locations', [AddressController::class, 'locations'])->name('addresses.locations');
        Route::resource('addresses', AddressController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('addresses.default');

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

Route::middleware(['auth', 'seller', 'throttle:mutations'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {

        Route::get('/dashboard', [SellerDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/earnings', [MoneyController::class, 'index'])->name('earnings.index');
        Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::get('/settlements', [SellerSettlementController::class, 'index'])->name('settlements.index');
        Route::get('/settlements/{reference}/proof', [SellerSettlementController::class, 'proof'])->name('settlements.proof');
        Route::get('/settlement-evidence/{event}', [SellerSettlementController::class, 'orderProof'])->whereNumber('event')->name('settlements.evidence');
        Route::post('/settlements/{reference}/confirm', [SellerSettlementController::class, 'confirm'])->name('settlements.confirm');
        Route::post('/settlements/{reference}/dispute', [SellerSettlementController::class, 'dispute'])->name('settlements.dispute');
        Route::post('/payouts', [PayoutController::class, 'requestSeller'])->name('payouts.store');
        Route::post('/payout-methods', [PayoutController::class, 'addMethod'])->name('payouts.methods.store');
        Route::delete('/payout-methods/{method}', [PayoutController::class, 'removeMethod'])->whereNumber('method')->name('payouts.methods.destroy');

        // Products
        Route::prefix('products')->name('products.')->group(function (): void {
            Route::controller(SellerProductDiscountController::class)->prefix('discounts')->name('discounts.')->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::put('/{discount}', 'update')->name('update');
                Route::delete('/{discount}', 'destroy')->name('destroy');
            });
            Route::controller(SellerProductController::class)->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/bulk', 'bulk')->name('bulk');
                Route::get('/{product}/edit', 'edit')->name('edit');
                Route::put('/{product}', 'update')->name('update');
                Route::patch('/{product}/status', 'updateStatus')->name('status');
                Route::patch('/{product}/quick', 'quickUpdate')->name('quick');
                Route::post('/{product}/duplicate', 'duplicate')->name('duplicate');
                Route::delete('/{product}', 'destroy')->name('destroy');
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

                Route::get('/{order}/waybill/view', [WaybillController::class, 'preview'])
                    ->name('waybill.view');

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

Route::middleware(['auth', 'logistics', 'hub.scope', 'throttle:mutations'])
    ->domain(Portal::host('logistics'))
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function () {

        Route::get('/company-announcements', [CompanyAnnouncementController::class, 'index'])->name('company-announcements.index');
        Route::get('/company-announcements/{announcement:public_code}', [CompanyAnnouncementController::class, 'show'])->whereUuid('announcement')->name('company-announcements.show');
        Route::post('/company-announcements/{announcement:public_code}/dismiss', [CompanyAnnouncementController::class, 'dismiss'])->whereUuid('announcement')->name('company-announcements.dismiss');
        Route::get('/dashboard', [LogisticsDashboardController::class, 'index'])
            ->name('dashboard');

        // Riders
        Route::prefix('riders')
            ->name('riders.')
            ->controller(RiderController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{rider}', 'show')->name('show');
                Route::get('/{rider}/documents/{document}', 'document')->whereIn('document', ['license', 'or-cr'])->name('document');
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
                Route::delete('/bags/{bag}/orders/{order}', 'removeOrder')->name('orders.destroy');
                Route::post('/bags/{bag}/seal', 'seal')->name('seal');
                Route::post('/bags/{bag}/dispatch', 'dispatch')->name('dispatch');
                Route::get('/bags/{bag}/manifest', 'manifest')->name('manifest');
                Route::post('/bags/{bag}/manifest/printed', 'markManifestPrinted')->name('manifest.printed');
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

                // Quick scan from inbound index: scan any parcel code to mark it received
                Route::post('/scan', 'scanIndex')
                    ->middleware('throttle:120,1')
                    ->name('scan.index');
            });

        Route::get('/rider-rate', [RiderRateController::class, 'index'])->name('rider-rate.index');
        Route::post('/rider-rate', [RiderRateController::class, 'store'])->name('rider-rate.store');

        Route::get('/reports/ledger', [MoneyController::class, 'index'])->name('money.index');
        Route::get('/receipts/{type}/{reference}', [MoneyReceiptController::class, 'show'])->whereIn('type', MoneyReceipts::TYPES)->name('receipts.show');
        Route::get('/money/rider-payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::get('/money/seller-settlements', [SellerSettlementController::class, 'index'])->name('settlements.index');
        Route::post('/money/seller-settlements', [SellerSettlementController::class, 'store'])->name('settlements.store');
        Route::post('/money/seller-settlements/{reference}/send', [SellerSettlementController::class, 'send'])->name('settlements.send');
        Route::post('/money/seller-settlements/{reference}/cancel', [SellerSettlementController::class, 'cancel'])->name('settlements.cancel');
        Route::get('/money/seller-settlements/{reference}/proof', [SellerSettlementController::class, 'proof'])->name('settlements.proof');
        Route::get('/money/settlement-evidence/{event}', [SellerSettlementController::class, 'orderProof'])->whereNumber('event')->name('settlements.evidence');
        Route::post('/money/seller-recoveries/{order}', [SellerSettlementController::class, 'recovery'])->whereNumber('order')->name('settlements.recovery');
        Route::post('/money/seller-refunds/{reference}', [SellerSettlementController::class, 'refund'])->name('settlements.refund');
        Route::post('/money/rider-payouts', [PayoutController::class, 'payRider'])->name('payouts.store');
        Route::get('/money/{section}', [MoneyMovementController::class, 'index'])->whereIn('section', ['cash', 'remittances'])->name('movements.index');
        Route::post('/money/cash', [MoneyMovementController::class, 'handover'])->name('movements.cash.store');
        Route::post('/money/cash/{reference}/settle', [MoneyMovementController::class, 'settle'])->name('movements.cash.settle');
        Route::post('/money/remittances', [MoneyMovementController::class, 'submitRemittance'])->name('movements.remittances.store');
        Route::get('/money/remittances/{reference}/proof', [MoneyMovementController::class, 'proof'])->name('money.proof');

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

Route::middleware(['auth', 'admin', 'throttle:mutations'])
    ->domain(Portal::host('admin'))
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/ledger', [MoneyController::class, 'index'])->name('ledger.index');
        Route::get('/money/seller-payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::get('/receipts/{type}/{reference}', [MoneyReceiptController::class, 'show'])->whereIn('type', MoneyReceipts::TYPES)->name('receipts.show');
        Route::get('/money/seller-settlements', [SellerSettlementController::class, 'index'])->name('settlements.index');
        Route::post('/money/seller-settlements/{reference}/resolve', [SellerSettlementController::class, 'resolve'])->name('settlements.resolve');
        Route::get('/money/seller-settlements/{reference}/proof', [SellerSettlementController::class, 'proof'])->name('settlements.proof');
        Route::get('/money/settlement-evidence/{event}', [SellerSettlementController::class, 'orderProof'])->whereNumber('event')->name('settlements.evidence');
        Route::post('/money/commission-returns/{order}', [SellerSettlementController::class, 'commissionReturn'])->whereNumber('order')->name('settlements.commission-return');
        Route::post('/money/seller-payouts/{reference}/{decision}', [PayoutController::class, 'decideSeller'])->whereIn('decision', ['approve', 'pay', 'reject'])->name('payouts.decide');
        Route::get('/money/refunds', [PayoutController::class, 'index'])->name('refunds.index');
        Route::post('/money/refunds/{reference}/pay', [PayoutController::class, 'payRefund'])->name('refunds.pay');
        Route::get('/money/{section}', [MoneyMovementController::class, 'index'])->whereIn('section', ['remittances', 'settings'])->name('movements.index');
        Route::post('/money/remittances/{reference}/{decision}', [MoneyMovementController::class, 'decideRemittance'])->whereIn('decision', ['verify', 'reject'])->name('movements.remittances.decide');
        Route::post('/money/settings', [MoneyMovementController::class, 'settings'])->name('movements.settings');
        Route::get('/money/remittances/{reference}/proof', [MoneyMovementController::class, 'proof'])->name('money.proof');

        // Registration management: type => [controller, route parameter]
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/{company}/documents/{document}', [CompanyController::class, 'document'])
            ->whereUuid('company')->whereIn('document', array_keys(Company::DOCUMENTS))->name('companies.document');
        Route::post('/companies/{company}/approve', [CompanyController::class, 'approve'])->whereUuid('company')->name('companies.approve');
        Route::post('/companies/{company}/reject', [CompanyController::class, 'reject'])->whereUuid('company')->name('companies.reject');
        Route::post('/companies/{company}/status', [CompanyController::class, 'accountStatus'])->whereUuid('company')->name('companies.status');

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

Route::middleware(['auth', 'logistics', 'hub.scope'])->domain(Portal::host('logistics'))->group(function (): void {
    Route::get('/logistics/delivery-proofs/{order:order_number}', [DeliveryProofController::class, 'show'])->name('logistics.delivery-proofs.show');
    Route::get('/logistics/delivery-proofs/{order:order_number}/image', [DeliveryProofController::class, 'image'])->name('logistics.delivery-proofs.image');
});
Route::middleware(['auth', 'admin'])->domain(Portal::host('admin'))->group(function (): void {
    Route::get('/admin/delivery-proofs/{order:order_number}', [DeliveryProofController::class, 'show'])->name('admin.delivery-proofs.show');
    Route::get('/admin/delivery-proofs/{order:order_number}/image', [DeliveryProofController::class, 'image'])->name('admin.delivery-proofs.image');
});
