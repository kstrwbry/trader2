<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RSIInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

class RSI implements IndicatorInterface
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
        $prev = $number->getPrevEntity();

        $outdatedIndex = $index-$number->getPeriod();

        /** @var KlineInterface|null $outdatedKline */
        $outdatedKline = $this->numbers[$outdatedIndex]?->getKline();

        $outdatedGain = $outdatedKline?->getGain() ?? 0.0;
        $outdatedLoss = $outdatedKline?->getLoss() ?? 0.0;

        $prevGainSum = $prev?->getGainSum() ?? 0.0;
        $prevLossSum = $prev?->getLossSum() ?? 0.0;

        $number->setGainSum($prevGainSum - $outdatedGain + $kline->getGain());
        $number->setLossSum($prevLossSum - $outdatedLoss + $kline->getLoss());

        $number->calcIndicator();
    }
}
