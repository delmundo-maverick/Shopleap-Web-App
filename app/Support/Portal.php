<?php

namespace App\Support;

use Illuminate\Http\Request;

class Portal
{
    public static function host(string $portal): string
    {
        return (string) parse_url(config("portals.{$portal}"), PHP_URL_HOST);
    }

    public static function isLogistics(?Request $request = null): bool
    {
        return ($request ?? request())->getHost() === self::host('logistics');
    }

    public static function url(string $portal, string $path = ''): string
    {
        return config("portals.{$portal}").'/'.ltrim($path, '/');
    }

    public static function route(string $portal, string $name, mixed $parameters = []): string
    {
        return self::url($portal, route($name, $parameters, false));
    }

    /** @return list<string> */
    public static function trustedHosts(): array
    {
        $hosts = [self::host('main'), self::host('logistics'), self::host('admin')];

        return array_values(array_map(fn (string $host): string => '^'.preg_quote($host, '/').'$', array_unique(array_filter([
            ...$hosts, ...config('portals.additional_hosts', []),
        ]))));
    }
}
