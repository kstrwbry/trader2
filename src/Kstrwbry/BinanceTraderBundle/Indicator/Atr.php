<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\ATRInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

use function abs;
use function max;

/**
 * Average True Range (ATR) - STATELESS
 *
 * Computes ATR in a rolling manner using the entity's stored prevEntity and outdated entity.
 * No in-memory arrays are required. The ATR value is stored on the entity.
 */
class Atr implements IndicatorInterface
{
    use IndicatorTrait;

    /**
     * @param IndicatorEntityInterface|ATRInterface $number
     * @param int $index
     *
     * @return void
     */
    protected function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcATR($number, $index);
    }

    /**
     * @param IndicatorEntityInterface|ATRInterface $number
     * @param int $index
     *
     * @return void
     */
    private function calcATR(IndicatorEntityInterface $number, int $index): void
    {
        /** @var ATRInterface $number */
        $period = $number->getPeriod();

        $high = $number->getHigh();
        $low = $number->getLow();

        /** @var ATRInterface|null $prevEntity */
        $prevEntity = $number->getPrevEntity();
        $prevClose = $prevEntity?->getClose() ?? $number->getClose();

        $tr = max(
            $high - $low,
            abs($high - $prevClose),
            abs($low - $prevClose)
        );

        $prevAtrSum = $prevEntity?->getAtrSum() ?? 0.0;

        /** @var ATRInterface|null $outdatedEntity */
        $outdatedEntity = $this->getOutdatedEntity($index);
        $outdatedTr = $outdatedEntity?->getTr() ?? 0.0;

        $atrSum = $prevAtrSum + $tr - $outdatedTr;

        $number->setTr($tr);
        $number->setAtrSum($atrSum);

        $atr = $atrSum / $period;
        $number->setAtr($atr);
    }
}
