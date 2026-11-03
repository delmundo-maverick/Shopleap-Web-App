<?php

namespace App\Services;

use App\Models\LogisticsProfile;
use Illuminate\Http\Request;

/**
 * The single place logistics controllers resolve "my hub".
 *
 * The hub is set by the hub.scope middleware. The fallback keeps commands,
 * tests and any non-HTTP caller working without the middleware.
 */
class HubContext
{
    public static function for(Request $request): ?LogisticsProfile
    {
        if ($request->attributes->has('hub')) {
            return $request->attributes->get('hub');
        }

        return $request->user()?->logisticsProfile;
    }

    public static function id(Request $request): ?int
    {
        return self::for($request)?->id;
    }

    public static function require(Request $request): LogisticsProfile
    {
        $hub = self::for($request);

        abort_unless($hub !== null, 403, 'No logistics hub is linked to this account.');

        return $hub;
    }
}
