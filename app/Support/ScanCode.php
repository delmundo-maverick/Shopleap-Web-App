<?php

namespace App\Support;

use App\Models\Order;

class ScanCode
{
    public static function orderId(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if (preg_match('/^SHOPLEAP:ORDER:(\d+)$/', $code, $matches)) {
            $code = str_pad((string) (int) $matches[1], 10, '0', STR_PAD_LEFT);
        }

        return Order::matchingPublicCode($code)->value('id');
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
