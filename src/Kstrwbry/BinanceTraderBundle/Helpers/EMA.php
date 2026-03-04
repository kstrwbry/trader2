<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Helpers;

use function count;
use function min;

/** Exponential moving average (EMA) */
class EMA
{
    public static function calcSingle(float $number, int $period, float $prevEMA = 0): float
    {
        $smoothingConst = 2 / ($period + 1);

        return ($smoothingConst * $number) + ((1 - $smoothingConst) * $prevEMA);
    }

    public static function calc(array $numbers, int $period): array
    {
        $cnt    = count($numbers);
        $period = min($cnt, $period);

        $EMA   = [];
        $EMA[] = $numbers[0];

        for ($day = 1; $day < $cnt; $day++) {
            $EMA[] = static::calcSingle($numbers[$day], $period, $EMA[$day - 1]);
        }

        return $EMA;
    }
}
