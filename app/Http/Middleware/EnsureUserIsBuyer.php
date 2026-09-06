<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsBuyer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'buyer') {
            abort(403, 'Unauthorized.');
        }

        if ($user->account_status !== 'active') {
            abort(403, 'Your account is not active. Please contact support.');
        }

        if (! $user->buyerProfile || $user->buyerProfile->status !== 'approved') {
            abort(403, 'Your buyer registration has not been approved yet.');
        }

        return $next($request);
    }
}
