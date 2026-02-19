<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Helpers\EMA;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

use function min;
use function sqrt;

/**
 * Standard deviation (StdDev) - STATELESS
 *
 * All calculation state is stored in the entity database columns.
 * This class reads from the entity chain (prevEntity, outdated entity via collection)
 * and writes results back to the current entity's properties.
 *
 * No in-memory arrays = supports re-calculation from persisted data.
 */
class StdDev implements IndicatorInterface
{
    use IndicatorTrait;

    public function __construct(
        /** @var $numbers ArrayCollection<StdDevInterface>|StdDevInterface[] */
        ArrayCollection $numbers,
    ) {
        $this->numbers = $numbers;

        $this->bulk();
    }

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
        $prevEntity = $number->getPrevEntity();
        $prevPrice = $prevEntity?->getClose() ?? 0.0;

        // Determine if this candle moved up or down
        $priceUpper = $prevPrice < $price ? $price : 0.0;
        $priceLower = $prevPrice > $price ? $price : 0.0;

        // Get outdated entity to shift out of the rolling window
        $outdatedEntity = $this->getOutdatedEntity($index, $period);
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

        // Calculate standard deviation by walking back through entity chain
        if (0 === $index) {
            $number->setStdDev(0.0);
            return;
        }

        $actualPeriod = min($index + 1, $period);
        $stdDevSum = 0.0;

        // Walk backwards through the entity chain to gather prices
        $currentEntity = $number;
        for ($i = 0; $i < $actualPeriod; $i++) {
            if (null === $currentEntity) {
                break;
            }
            $currentPrice = $currentEntity->getClose();
            $stdDevSum += ($currentPrice - $avg) ** 2;
            $currentEntity = $currentEntity->getPrevEntity();
        }

        $stdDevValue = sqrt($stdDevSum / $actualPeriod);
        $number->setStdDev($stdDevValue);
    }

    /**
     * Calculates exponential moving average for the lower band.
     * Reads from prevEntity, writes to current entity - fully stateless.
     */
    private function calcEMA(StdDevInterface $number): void
    {
        $number->setEmaLower(EMA::calcSingle(
            $number->getClose(),
            $number->getPeriod(),
            $number->getPrevEntity()?->getEmaLower() ?? 0.0
        ));
    }

    /**
     * Helper to get the entity that should be shifted out of the rolling window.
     * This is the entity at position (index - period) in the collection.
     */
    private function getOutdatedEntity(int $index, int $period): ?StdDevInterface
    {
        $outdatedIndex = $index - $period;
        if ($outdatedIndex < 0) {
            return null;
        }

        return $this->numbers->get($outdatedIndex);
    }
}
