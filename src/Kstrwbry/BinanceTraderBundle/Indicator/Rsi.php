<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RSIInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

class Rsi implements IndicatorInterface
{
    use IndicatorTrait;

    /**
     * @param IndicatorEntityInterface|RSIInterface $number
     * @param int $index
     *
     * @return void
     */
    protected function calc(IndicatorEntityInterface $number, int $index)
    {
        $this->calcRSI($index, $number);
    }

    private function calcRSI(int $index, RSIInterface $number): void
    {
        $kline = $number->getKline();
        /** @var RSIInterface $prev */
        $prev = $number->getPrevEntity();

        /** @var KlineInterface|null $outdatedKline */
        $outdatedKline = $number->getOutdatedEntity()?->getKline();

        $outdatedGain = $outdatedKline?->getGain() ?? 0.0;
        $outdatedLoss = $outdatedKline?->getLoss() ?? 0.0;

        $prevGainSum = $prev?->getGainSum() ?? 0.0;
        $prevLossSum = $prev?->getLossSum() ?? 0.0;

        $number->setGainSum($prevGainSum - $outdatedGain + $kline->getGain());
        $number->setLossSum($prevLossSum - $outdatedLoss + $kline->getLoss());

        $period = min($number->getKline()->getRunIndex() + 1, $number->getPeriod());

        $number->setAvgGain($this->calcAvg($number->getGainSum(), $period));
        $number->setAvgLoss($this->calcAvg($number->getLossSum(), $period));
    }

    private function calcAvg(
        float $sum,
        int $period
    ): float {
        if(0.0 >= $sum || 0 === $period) {
            return 0.0;
        }

        return $sum / (float)$period;
    }
}
