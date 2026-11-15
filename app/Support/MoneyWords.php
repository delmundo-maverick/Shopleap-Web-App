<?php

namespace App\Support;

use App\Services\LedgerService;

class MoneyWords
{
    public static function pesos(string $amount): string
    {
        $cents = LedgerService::cents($amount);
        $pesos = intdiv(abs($cents), 100);
        $centavos = abs($cents) % 100;

        return ucfirst(($cents < 0 ? 'minus ' : '').self::integer($pesos).' '.($pesos === 1 ? 'peso' : 'pesos')
            .' and '.self::integer($centavos).' '.($centavos === 1 ? 'centavo' : 'centavos').' only');
    }

    private static function integer(int $number): string
    {
        $small = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        if ($number < 20) {
            return $small[$number];
        }
        if ($number < 100) {
            $tens = [2 => 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

            return $tens[intdiv($number, 10)].($number % 10 ? '-'.$small[$number % 10] : '');
        }
        foreach ([1000000000000 => 'trillion', 1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand', 100 => 'hundred'] as $unit => $word) {
            if ($number >= $unit) {
                return self::integer(intdiv($number, $unit)).' '.$word.($number % $unit ? ' '.self::integer($number % $unit) : '');
            }
        }

        throw new \InvalidArgumentException('Unsupported amount.');
    }
}
