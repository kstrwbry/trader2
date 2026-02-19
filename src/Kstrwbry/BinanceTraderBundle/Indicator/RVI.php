<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Indicator;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RVIInterface;
use Doctrine\Common\Collections\ArrayCollection;

use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorTrait;
use function array_shift;

/** Relative volatility index (RVI) */
class RVI implements IndicatorInterface
{
    use IndicatorTrait;

    public function __construct(
        /** @var ArrayCollection|RVIInterface[] */
        ArrayCollection $numbers,
    ) {
        $this->numbers = $numbers;

        $this->bulk();
    }

    protected function calc(IndicatorEntityInterface $number, int $index): void
    {
        $this->calcRVI($number, $index);
    }
    private function calcRVI(RVIInterface $number, int $index): void
    {
        /* Calculation moved to DB Layer */
        #$value = $number->getStdDev()->getStdDev();
        #$this->stdDev[] = $value;
        #$prevValue = $this->stdDev[$index-1] ?? 0.0;

        #$upperEMA = $value > $prevValue ? $value : 0.0;
        #$lowerEMA = $value < $prevValue ? $value : 0.0;
        #$this->upperEMA[] = $upperEMA;
        #$this->lowerEMA[] = $lowerEMA;
        #$number->setUpperEMA($upperEMA);
        #$number->setLowerEMA($lowerEMA);

        $this->upperEMA[] = $number->getUpperEMA();
        $this->lowerEMA[] = $number->getLowerEMA();

        if($index >= $number->getPeriod()) {
            $this->upperSum -= array_shift($this->upperEMA);
            $this->lowerSum -= array_shift($this->lowerEMA);
        }
        $this->upperSum += $upperEMA;
        $this->lowerSum += $lowerEMA;
        $number->setUpperEMASum($this->upperSum);
        $number->setLowerEMASum($this->lowerSum);

        if($this->upperSum === 0.0) $this->upperSum = 0.001;
        if($this->lowerSum === 0.0) $this->lowerSum = 0.001;

        $rvi = 100 * ($this->upperSum / ($this->upperSum + $this->lowerSum));
        $number->setRvi($rvi);
    }
}
