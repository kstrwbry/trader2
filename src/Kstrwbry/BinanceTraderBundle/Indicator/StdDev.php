<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Helpers\EMA;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\MACDInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;

use function min;
use function sqrt;

/** Standard deviation (StdDev) */
class StdDev implements IndicatorInterface
{
    use IndicatorTrait;

    private array $prices = [];
    private array $pricesUpper = [];
    private array $pricesLower = [];

    private float $priceSum;
    private float $upperSum;
    private float $lowerSum;

    private float $stdDevSum;
    private float $stdDevSumUpper;
    private float $stdDevSumLower;

    private array $ema = [];
    private array $upperSumEMA = [];
    private array $lowerSumEMA = [];

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
     * @return void
     */
    private function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcStdDev($number, $index);
        $this->calcEMA($number, $index);
    }

    private function calcStdDev(StdDevInterface $number, int $index)
    {
        $period = $number->getPeriod();
        $prevPrice = $this->prices[$index-1] ?? 0;

        $price = $this->prices[$index] = $number->getClose();
        $priceUpper = $this->pricesUpper[$index] = $prevPrice < $price ? $price : 0;
        $priceLower = $this->pricesLower[$index] = $prevPrice > $price ? $price : 0;

        $outdatedPrice = $this->prices[$index-$period] ?? 0;
        $outdatedPriceUpper = $this->pricesUpper[$index-$period] ?? 0;
        $outdatedPriceLower = $this->pricesLower[$index-$period] ?? 0;

        $this->priceSum += $price - $outdatedPrice;
        $this->upperSum += $priceUpper - $outdatedPriceUpper;
        $this->lowerSum += $priceLower - $outdatedPriceLower;

        $number->setSum($this->priceSum);
        $number->setSumUpper($this->upperSum);
        $number->setSumLower($this->lowerSum);

        $avgPeriod = min($index+1, $period);
        $avg = $this->priceSum / $avgPeriod;
        $number->setAvg($avg);

        // following code could be placed in an own method

        $number->calcIndicator();

        if(0 === $index) {
            $number->setStdDev(0);
            return;
        }

        $period = min($index+1, $number->getPeriod());
        $avg = $number->getAvg();

        $stdDevSum = 0;
        for($i = 0; $i < $period; $i++) {
            $stdDevSum += ($this->prices[$index - $i] - $avg) ** 2;
        }

        $stdDevValue = sqrt($stdDevSum / $period);

        $number->setStdDev($stdDevValue);
    }

    private function calcEMA(StdDevInterface $number): void
    {
        $number->setEmaLower(EMA::calcSingle(
            $number->getClose(),
            $number->getPeriod(),
            $number->getPrevEntity()?->getEmaLower() ?? 0.0),
        );
    }
}
