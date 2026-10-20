<?php

namespace App\Http\Middleware;

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourier
{
    /** Use `courier` for any signed-in courier, `courier:approved` for working endpoints. */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'courier') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->account_status !== 'active') {
            return response()->json(['message' => 'Your account is not active. Please contact your sorting center.'], 403);
        }

        $profile = CourierProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'No rider profile found for this account.'], 403);
        }

        if ($mode === 'approved') {
            if ($profile->status !== 'approved') {
                return response()->json([
                    'message' => $profile->status === 'rejected'
                        ? 'Your application was not approved.'
                        : 'Your application is waiting for hub approval.',
                    'status' => $profile->status,
                    'rejection_reason' => $profile->rejection_reason,
                ], 403);
            }

            if (! $profile->is_active) {
                return response()->json(['message' => 'Your rider account was deactivated by your sorting center.', 'status' => 'inactive'], 403);
            }

            if (! LogisticsProfile::available()->whereKey($profile->logistics_profile_id)->exists()) {
                return response()->json(['message' => 'Your sorting center is not available right now.', 'status' => 'hub_unavailable'], 403);
            }
        }

        $request->attributes->set('courier', $profile);

        return $next($request);
    }
}
