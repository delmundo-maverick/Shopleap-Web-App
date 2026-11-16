<?php

namespace App\Support;

use App\Models\Order;

class ScanCode
{
    public static function orderId(string $code): ?int
    {
        if (strlen($code) > 2048) {
            return null;
        }

        $code = trim(rawurldecode($code));

        if (preg_match('~^https?://~i', $code)) {
            $path = parse_url($code, PHP_URL_PATH);

            if (! is_string($path)) {
                return null;
            }

            $code = rawurldecode(basename(rtrim($path, '/')));
        }

        $code = strtoupper(trim($code));

        if (preg_match('/^SHOPLEAP:ORDER:(.+)$/', $code, $matches)) {
            $code = $matches[1];
        }

        $query = Order::matchingPublicCode($code);

        if (ctype_digit($code)) {
            $query->orWhere('waybill_code', str_pad(ltrim($code, '0') ?: '0', 10, '0', STR_PAD_LEFT));
        }

        return $query->value('id');
    }

    public static function bagCode(string $code): ?string
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        if (preg_match('/^SHOPLEAP:BAG:(.+)$/i', $code, $matches)) {
            return trim($matches[1]);
        }

        return $code;
    }
}
