<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Helpers;

use function array_slice;
use function array_sum;
use function count;
use function min;

/** Simple moving average (SMA) */
class SMA
{
    public static function calcSingle(array $numbers, int $period, int $offset = null): float
    {
        $offset ??= $period;
        $cnt = count($numbers);

        if($period + $offset > $cnt) {
            $period = $cnt - $offset;
        }

        $period = min($period, $cnt);

        return static::sum($numbers, $period, $offset) / $period;
    }

    public static function calc(array $numbers, int $period): array
    {
        $period = min($period, count($numbers));

        $numbers = array_map(
            static fn(float $number): float => $number / $period,
            $numbers,
        );

        $currentSMA = static::sum($numbers, $period, 0);

        $SMA = [];
        foreach($numbers as $index => $number) {
            if($index < $period) {
                $SMA[] = $currentSMA;
                continue;
            }

            $currentSMA -= $numbers[$index - $period];
            $currentSMA += $number;
            $SMA[] = $currentSMA;
        }

        return $SMA;
    }

    private static function sum(array $numbers, int $length, int $offset): float
    {
        return array_sum(array_slice($numbers, $offset, $length));
    }
}
