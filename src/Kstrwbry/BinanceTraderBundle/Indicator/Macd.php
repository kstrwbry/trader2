<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Helpers\EMA;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\MACDInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

class Macd implements IndicatorInterface
{
    use IndicatorTrait;

    /**
     * @param IndicatorEntityInterface|MACDInterface $number
     * @param int $index
     *
     * @return void
     */
    protected function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcEMA($number);
        $this->calcSignal($number);
    }

    private function calcEMA(MACDInterface $number): void
    {
        $number->setShortEMA(EMA::calcSingle(
            $number->getClose(),
            $number->getShortPeriod(),
            $number->getPrevEntity()?->getShortEMA() ?? 0.0),
        );

        $number->setlongEMA(EMA::calcSingle(
            $number->getClose(),
            $number->getlongPeriod(),
            $number->getPrevEntity()?->getLongEMA() ?? 0.0),
        );

        $number->setMacd($number->getShortEMA() - $number->getLongEMA());

        $number->setSignalEMA(EMA::calcSingle(
            $number->getMacd(),
            $number->getSignalPeriod(),
            $number->getPrevEntity()?->getSignalEMA() ?? 0.0),
        );
    }

    private function calcSignal(MACDInterface $number): void
    {
        $number->calcSignal();
    }
}
