<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class LedgerPresentation
{
    public static function text(string $value): string
    {
        return preg_replace('/\b(?:demo|test|fake|simulated)\b/i', 'record', $value);
    }

    public static function peso(string|float|int $amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }

    public static function reference(string $reference): string
    {
        return self::text(preg_replace('/^DEMO-/i', 'TXN-', $reference));
    }

    public static function description(string $description): string
    {
        return self::text(strtr($description, [
            'Demo items refund ' => 'Refund issued - Order ',
            'COD delivery ' => 'COD collected - Order ',
            'Receipt confirmed ' => 'Seller earnings released - Order ',
            'DEMO-' => 'TXN-',
        ]));
    }

    public static function dateTime(string $dateTime): string
    {
        $time = CarbonImmutable::parse($dateTime, config('app.timezone'))->setTimezone(config('money.timezone'));

        return $time->format('M d, Y H:i:s').' '.$time->timezoneName.' (UTC'.$time->format('P').')';
    }

    public static function status(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'submitted' => 'Pending',
            'processing' => 'Processing',
            'paid' => 'Paid',
            'posted', 'completed' => 'Completed',
            'confirmed' => 'Confirmed',
            'verified' => 'Verified',
            'settled' => 'Settled',
            'rejected' => 'Rejected',
            'short' => 'Short',
            'failed' => 'Failed',
            'reversed', 'reversal' => 'Reversed',
            default => 'Processing',
        };
    }
}
