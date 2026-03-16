<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RVIInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

/**
 * Relative volatility index (RVI) - STATELESS
 */
class Rvi implements IndicatorInterface
{
    use IndicatorTrait;

    /**
     * @param IndicatorEntityInterface|RVIInterface $number
     * @param int $index
     *
     * @return void
     */
    protected function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcRVI($number, $index);
    }

    /**
     * @param IndicatorEntityInterface|RVIInterface $number
     * @param int $index
     *
     * @return void
     */
    private function calcRVI(RVIInterface $number, int $index): void
    {
        $currentUpperEMA = $number->getUpperEMA();
        $currentLowerEMA = $number->getLowerEMA();

        /** @var RVIInterface $prevEntity */
        $prevEntity = $number->getPrevEntity();
        $prevUpperSum = $prevEntity?->getUpperEMASum() ?? 0.0;
        $prevLowerSum = $prevEntity?->getLowerEMASum() ?? 0.0;

        /** @var RVIInterface $outdatedEntity */
        $outdatedEntity = $number->getOutdatedEntity();
        $outdatedUpperEMA = $outdatedEntity?->getUpperEMA() ?? 0.0;
        $outdatedLowerEMA = $outdatedEntity?->getLowerEMA() ?? 0.0;

        $upperSum = $prevUpperSum + $currentUpperEMA - $outdatedUpperEMA;
        $lowerSum = $prevLowerSum + $currentLowerEMA - $outdatedLowerEMA;

        $number->setUpperEMASum($upperSum);
        $number->setLowerEMASum($lowerSum);
    }
}
