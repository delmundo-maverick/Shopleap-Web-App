<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ShopVoucher;
use App\Policies\OrderPolicy;
use App\Policies\ShopVoucherPolicy;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('portal-session', function (Application $app, string $name, array $config): SessionGuard {
            $hostKey = substr(hash('sha256', $app['request']->getHost()), 0, 12);

            return $app['auth']->createSessionDriver($name.'_'.$hostKey, $config);
        });

        if (config('portals.force_https')) {
            URL::forceScheme('https');
        }

        View::composer('components.navbars.seller', function (\Illuminate\View\View $view): void {
            $sellerId = auth()->id();
            $view->with('pendingReturns', OrderReturn::where('seller_id', $sellerId)->whereIn('status', ['requested', 'shipped_back'])->count());
            $view->with('lowStockCount', Product::where('seller_id', $sellerId)->stockLevel('low')->count());
        });

        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(ShopVoucher::class, ShopVoucherPolicy::class);
    }
}
