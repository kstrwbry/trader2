<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\Entity\StdDev;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;

/**
 * Stateful internal builder for StdDev entities.
 * Maintains the sliding window of StdDev entities required for the rolling calculation.
 *
 * Not registered as a standalone EntityBuilder — instantiated internally by RviBuilder.
 */
class StdDevBuilder
{
    /** @var StdDevInterface[] */
    private array $arrStdDev = [];

    public function __construct(
        private readonly int $period = 14,
    ) {}

    public function build(KlineInterface $kline): StdDevInterface
    {
        // Replicate the pattern from FetchBinanceDataCommand:
        //   lastStdDev  = current last element (before any shift)
        //   outdatedStdDev = first element shifted out once window is full
        $lastStdDev = !empty($this->arrStdDev) ? end($this->arrStdDev) : null;

        $outdatedStdDev = null;
        if (count($this->arrStdDev) >= $this->period) {
            $outdatedStdDev = array_shift($this->arrStdDev);
        }

        $stdDev = new StdDev($kline, $lastStdDev, $outdatedStdDev, $this->period);

        $this->arrStdDev[] = $stdDev;

        return $stdDev;
    }
}
