<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ShopVoucher;
use App\Policies\OrderPolicy;
use App\Policies\ShopVoucherPolicy;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('mutations', fn (Request $request): Limit => $request->isMethodSafe()
            ? Limit::none()
            : Limit::perMinute(120)->by('mutations:'.($request->user()?->id ?? $request->ip())));

        RateLimiter::for('courier-reads', fn (Request $request): Limit => $request->isMethodSafe()
            ? Limit::perMinute(120)->by('courier-reads:'.($request->user()?->id ?? $request->ip()))
            : Limit::none());

        RateLimiter::for('messaging', fn (Request $request): Limit => Limit::perMinute(60)
            ->by('messaging:'.($request->user()?->id ?? $request->ip())));

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
