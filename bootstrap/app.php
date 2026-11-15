<?php

use App\Http\Middleware\EnforcePortalHost;
use App\Http\Middleware\EnsureCourier;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsBuyer;
use App\Http\Middleware\EnsureUserIsLogistics;
use App\Http\Middleware\EnsureUserIsSeller;
use App\Http\Middleware\IsolatePortalSession;
use App\Http\Middleware\ResolveHubScope;
use App\Support\Portal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->trustHosts(at: fn (): array => Portal::trustedHosts(), subdomains: false);
        $middleware->append(EnforcePortalHost::class);
        $middleware->web(append: [IsolatePortalSession::class]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'logistics' => EnsureUserIsLogistics::class,
            'hub.scope' => ResolveHubScope::class,
            'seller' => EnsureUserIsSeller::class,
            'buyer' => EnsureUserIsBuyer::class,
            'courier' => EnsureCourier::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['account_number', 'account_number_masked', 'refund_account_number']);
        $exceptions->render(function (PostTooLargeException $exception, Request $request): ?JsonResponse {
            if ($request->is('api/courier/deliveries/*/delivered')) {
                return response()->json(['message' => 'The upload request is too large. Send a proof photo of at most 5 MB.'], 413);
            }

            return null;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
