<?php

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsolatePortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $sessionHost = $request->session()->get('portal_host');
        if ($sessionHost && $sessionHost !== $host) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('portal_host', $host);

            return redirect()->route('login');
        }
        $request->session()->put('portal_host', $host);

        $user = $request->user();
        if ($user && ! in_array($user->role, Portal::loginRoles($request), true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort_if($request->expectsJson(), 403, 'Unauthorized.');

            return redirect()->route('login');
        }

        return $next($request);
    }
}
