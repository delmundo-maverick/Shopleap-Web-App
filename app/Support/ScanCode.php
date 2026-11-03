<?php

namespace App\Support;

/**
 * Parses the codes printed on waybills and bag manifests.
 *
 * Waybill QR:  SHOPLEAP:ORDER:42
 * Waybill 1D:  0000000042
 * Bag QR:      SHOPLEAP:BAG:SB-261031-ABCDE
 */
class ScanCode
{
    public static function orderId(string $code): ?int
    {
        $code = trim($code);

        if (preg_match('/^SHOPLEAP:ORDER:(\d+)$/i', $code, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^0*(\d+)$/', $code, $matches)) {
            return (int) $matches[1];
        }

        return null;
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
