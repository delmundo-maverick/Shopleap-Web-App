<?php

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePortalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up')) {
            return $next($request);
        }

        if (config('portals.force_https') && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 308);
        }

        $isLogistics = Portal::isLogistics($request);
        config(['session.cookie' => config('portals.session_cookie').'_'.substr(hash('sha256', $request->getHost()), 0, 12)]);
        if (! $request->is('api/*')) {
            app('session')->driver()->setName(config('session.cookie'));
        }
        $logisticsPath = $request->is('logistics', 'logistics/*', 'register/logistics', 'register/logistics/*');

        if ($logisticsPath && ! $isLogistics) {
            $path = $request->is('register/logistics') ? '/register' : $request->getPathInfo();

            return redirect()->away(Portal::url('logistics', $path).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 303);
        }

        if ($isLogistics && $request->is('register/logistics')) {
            return redirect()->away(Portal::url('logistics', 'register'), 303);
        }

        if ($request->is('admin', 'admin/*') && $request->getHost() !== Portal::host('admin')) {
            return redirect()->away(Portal::url('admin', $request->getRequestUri()), 303);
        }

        if ($isLogistics) {
            abort_unless($request->is(
                '/', 'login', 'logout', 'register', 'register/logistics/*', 'logistics/*',
                'forgot-password', 'forgot-password/*', 'terms', 'terms/*', 'privacy', 'privacy/*',
                'policies/*', 'help', 'help/*', 'messages', 'messages/*', 'notifications', 'notifications/*',
            ), 404);
        } elseif ($request->getHost() === Portal::host('admin') && Portal::host('admin') !== Portal::host('main')) {
            abort_unless($request->is('/', 'admin', 'admin/*', 'login', 'logout', 'forgot-password', 'forgot-password/*', 'help', 'help/*', 'messages', 'messages/*', 'notifications', 'notifications/*'), 404);
        }

        if (! $request->isMethodSafe() && $request->headers->has('Origin') && ! $request->is('api/*')) {
            abort_unless(rtrim($request->header('Origin'), '/') === $request->getSchemeAndHttpHost(), 419);
        }

        $response = $next($request);
        if ($isLogistics && $request->is('login', 'register', 'register/*', 'forgot-password*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
