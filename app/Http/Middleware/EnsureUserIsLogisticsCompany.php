<?php

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLogisticsCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Portal::isLogistics($request) && $request->user()?->role === 'logistics_company', 403);
        abort_unless($request->user()->company, 403);

        if (! $request->routeIs('company.status') && ($request->user()->company->status !== 'approved' || $request->user()->account_status !== 'active')) {
            abort_if($request->expectsJson(), 403, 'Your company account is not approved and active.');

            return redirect()->route('company.status');
        }

        return $next($request);
    }
}
