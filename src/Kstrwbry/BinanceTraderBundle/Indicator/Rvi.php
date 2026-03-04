<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RVIInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

/**
 * Relative volatility index (RVI) - STATELESS
 *
 * All calculation state is stored in the entity database columns (upperEMASum, lowerEMASum).
 * This class reads from the entity chain (prevEntity, outdated entity via collection)
 * and writes results back to the current entity's properties.
 *
 * The entity's own calcIndicator() method performs the final RVI calculation.
 * No in-memory arrays = supports re-calculation from persisted data.
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
     * Accumulates upper/lower EMA values in rolling sums and updates the
     * entity's upperEMASum and lowerEMASum properties.
     *
     * The entity's constructor has already computed upperEMA and lowerEMA from
     * the current vs. previous StdDev values. This method maintains the rolling
     * sum of those values over the configured period.
     *
     * All state is read from and written to entity properties - no in-memory caching.
     *
     * The final RVI calculation (100 * upperSum / (upperSum + lowerSum)) is
     * performed by the entity's own calcIndicator() method.
     */
    private function calcRVI(RVIInterface $number, int $index): void
    {
        // The entity's constructor set upperEMA and lowerEMA based on StdDev comparison.
        $currentUpperEMA = $number->getUpperEMA();
        $currentLowerEMA = $number->getLowerEMA();

        // Get previous entity's sums to build upon
        /** @var RVIInterface $prevEntity */
        $prevEntity = $number->getPrevEntity();
        $prevUpperSum = $prevEntity?->getUpperEMASum() ?? 0.0;
        $prevLowerSum = $prevEntity?->getLowerEMASum() ?? 0.0;

        // Get the outdated entity to shift out of the rolling window
        /** @var RVIInterface $outdatedEntity */
        $outdatedEntity = $this->getOutdatedEntity($index);
        $outdatedUpperEMA = $outdatedEntity?->getUpperEMA() ?? 0.0;
        $outdatedLowerEMA = $outdatedEntity?->getLowerEMA() ?? 0.0;

        // Calculate new rolling sums: previous sum + current value - outdated value
        $upperSum = $prevUpperSum + $currentUpperEMA - $outdatedUpperEMA;
        $lowerSum = $prevLowerSum + $currentLowerEMA - $outdatedLowerEMA;

        // Store the accumulated sums on the entity so its calcIndicator() can
        // compute the final RVI value from them.
        $number->setUpperEMASum($upperSum);
        $number->setLowerEMASum($lowerSum);

        // NOTE: We do NOT calculate the final RVI value here. That's the entity's
        // responsibility in its calcIndicator() method, which will be called by
        // Strategy::addKline() immediately after this.
    }
}
