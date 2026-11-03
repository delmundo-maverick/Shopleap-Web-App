<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the hub for the signed-in logistics user once per request and
 * exposes it as the "hub" request attribute. Controllers read it through
 * App\Services\HubContext so there is a single source of truth.
 */
class ResolveHubScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Super admins are not tied to a hub. Admin screens stay unscoped.
        if ($user->role === 'super_admin') {
            $request->attributes->set('hub', null);

            return $next($request);
        }

        $hub = $user->logisticsProfile;

        abort_unless(
            $hub && $hub->status === 'approved',
            403,
            'Your logistics registration has not been approved yet.'
        );

        $request->attributes->set('hub', $hub);

        return $next($request);
    }
}
