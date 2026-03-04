<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Helpers;

use function count;

/** Cumulative moving average (CMA) */
class CMA
{
    public static function calcSingle(
        float $number,
        int $index,
        float $prevCMA = 0,
    ): float {
        return ($number + ($prevCMA * $index)) / ($index+1);
    }

    public static function calc(array $numbers): array
    {
        $period = count($numbers);

        $CMA = [];
        $CMA[] = $numbers[0];

        for ($i = 1; $i < $period; $i++) {
            $CMA[] = self::calcSingle($numbers[$i], $i, $CMA[$i - 1]);
        }

        return $CMA;
    }
}
