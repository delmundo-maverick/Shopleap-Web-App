<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLogistics
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'logistics') {
            abort(403, 'Unauthorized.');
        }

        if ($user->account_status !== 'active') {
            abort(403, $user->account_status === 'suspended' ? 'Your hub account has been suspended. Please contact support.' : 'Your account is not active. Please contact support.');
        }

        if (! $user->logisticsProfile || $user->logisticsProfile->status !== 'approved') {
            abort_if($request->expectsJson(), 403, 'Your logistics registration has not been approved yet.');

            return redirect()->route('logistics.application');
        }

        if ($user->logisticsProfile->company_id !== null && $user->logisticsProfile->manager_password_set_at === null) {
            abort(403, 'Please set your password using the invitation email. Ask your company to resend it if needed.');
        }

        return $next($request);
    }
}
