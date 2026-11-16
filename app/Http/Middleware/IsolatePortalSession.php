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

        if ($user?->role === 'logistics' && ! $request->session()->has('logistics_session_version') && Auth::viaRemember()
            && $user->logistics_login_suspended_at === null && $user->account_status === 'active') {
            $request->session()->put('logistics_session_version', (int) $user->logistics_session_version);
            $user->forceFill(['last_login_at' => now()])->save();
        }

        if ($user?->role === 'logistics' && ($user->logistics_login_suspended_at !== null
            || (int) $request->session()->get('logistics_session_version', 0) !== (int) $user->logistics_session_version)) {
            $message = $user->logistics_login_suspended_at !== null
                ? 'Your manager login has been suspended. Please contact your company.'
                : 'Your session has ended. Please sign in again.';
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort_if($request->expectsJson(), 403, $message);

            return redirect()->route('login')->with('status', $message);
        }

        return $next($request);
    }
}
