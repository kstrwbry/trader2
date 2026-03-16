<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Helpers\EMA;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

use function array_shift;
use function min;

/**
 * Standard deviation (StdDev) - STATELESS
 */
class StdDev implements IndicatorInterface
{
    use IndicatorTrait;

    /**
     * @param IndicatorEntityInterface|StdDevInterface $number
     * @param int $index
     *
     * @return void
     */
    protected function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcStdDev($number, $index);
        $this->calcEMA($number);
    }

    /**
     * Calculates rolling sums (price, upper, lower), average, and standard deviation.
     *
     * All intermediate values are read from entity chain and written to entity properties.
     * No in-memory state is maintained between calls.
     */
    private function calcStdDev(StdDevInterface $number, int $index): void
    {
        $period = $number->getPeriod();
        $price = $number->getClose();

        // Get previous entity to determine price movement direction
        /** @var StdDevInterface|null $prevEntity */
        $prevEntity = $number->getPrevEntity();
        $prevPrice = $prevEntity?->getClose() ?? 0.0;

        $lastPrices = $prevEntity?->getLastPrices() ?? [];
        $lastPrices[] = $price;

        if ($period < ($number->getKline()->getRunIndex() + 1)) {
            array_shift($lastPrices);
        }
        $number->setLastPrices($lastPrices);

        // Determine if this candle moved up or down
        $priceUpper = $prevPrice < $price ? $price : 0.0;
        $priceLower = $prevPrice > $price ? $price : 0.0;

        // Get outdated entity to shift out of the rolling window
        /** @var StdDevInterface $outdatedEntity */
        $outdatedEntity = $number->getOutdatedEntity();
        $outdatedPrice = $outdatedEntity?->getClose() ?? 0.0;

        $outdatedPrevPrice = $outdatedEntity?->getPrevEntity()?->getClose() ?? 0.0;
        $outdatedPriceUpper = $outdatedPrevPrice < $outdatedPrice ? $outdatedPrice : 0.0;
        $outdatedPriceLower = $outdatedPrevPrice > $outdatedPrice ? $outdatedPrice : 0.0;

        // Calculate rolling sums by adjusting previous entity's sums
        $prevSum = $prevEntity?->getSum() ?? 0.0;
        $prevSumUpper = $prevEntity?->getSumUpper() ?? 0.0;
        $prevSumLower = $prevEntity?->getSumLower() ?? 0.0;

        $priceSum = $prevSum + $price - $outdatedPrice;
        $sumUpper = $prevSumUpper + $priceUpper - $outdatedPriceUpper;
        $sumLower = $prevSumLower + $priceLower - $outdatedPriceLower;

        $number->setSum($priceSum);
        $number->setSumUpper($sumUpper);
        $number->setSumLower($sumLower);

        // Calculate average
        $avgPeriod = min($index + 1, $period);
        $avg = $priceSum / $avgPeriod;
        $number->setAvg($avg);
    }

    /**
     * Calculates exponential moving average for the lower band.
     * Reads from prevEntity, writes to current entity - fully stateless.
     */
    private function calcEMA(StdDevInterface $number): void
    {
        /** @var StdDevInterface|null $prevEntity */
        $prevEntity = $number->getPrevEntity();
        $number->setEma(EMA::calcSingle(
            $number->getClose(),
            $number->getPeriod(),
            $prevEntity?->getEma() ?? 0.0
        ));

        $number->setEmaLower(EMA::calcSingle(
            $number->getClose(),
            $number->getPeriod(),
            $prevEntity?->getEmaLower() ?? 0.0
        ));

        $number->setEmaLower(EMA::calcSingle(
            $number->getClose(),
            $number->getPeriod(),
            $prevEntity?->getEmaUpper() ?? 0.0
        ));
    }
}
