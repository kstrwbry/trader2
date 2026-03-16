<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use Doctrine\ORM\Mapping as ORM;

use function sqrt;

/**
 * Base entity for Standard Deviation (StdDev).
 * Used as a dependency of RVI — not a top-level indicator in the strategy config.
 */
abstract class StdDev implements StdDevInterface
{
    use IndicatorEntityTrait;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['default' => 14, 'unsigned' => true])]
    protected readonly int $period;
    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;

    #[ORM\Column(name:'std_dev', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $stdDev = 0.0;

    #[ORM\Column(name:'std_dev_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $sum = 0.0;
    #[ORM\Column(name:'std_dev_avg', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $avg = 0.0;
    #[ORM\Column(name:'last_prices', type:'json', nullable:false)]
    protected array $lastPrices = [];

    #[ORM\Column(name:'std_dev_sum_upper', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $sumUpper = 0.0;
    #[ORM\Column(name:'std_dev_sum_lower', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $sumLower = 0.0;

    #[ORM\Column(name:'ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $ema = 0.0;
    #[ORM\Column(name:'ema_upper', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $emaUpper = 0.0;
    #[ORM\Column(name:'ema_lower', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $emaLower = 0.0;

    #[ORM\Column(name:'mode', type:'string', length:20, nullable:false, options:['default' => self::MODE_EMA])]
    protected string $mode;

    public function __construct(
        int                  $id,
        KlineInterface       $kline,
        StdDevInterface|null $prevEntity,
        int                  $period = 14,
        string               $mode = self::MODE_EMA,
    ) {
        $this->id           = $id;
        $this->kline        = $kline;
        $this->prevEntityId = $prevEntity?->getId();
        $this->prevEntity   = $prevEntity;

        $this->run    = $kline->getRun();
        $this->mode   = $mode;
        $this->period = $period;
        $this->close  = $kline->getClose();
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getAvg(): float
    {
        return $this->avg;
    }

    public function getLastPrices(): array
    {
        return $this->lastPrices;
    }

    public function setLastPrices(array $lastPrices): static
    {
        $this->lastPrices = $lastPrices;
        return $this;
    }

    public function setAvg(float $avg): static
    {
        $this->avg = $avg;
        return $this;
    }

    public function getSum(): float
    {
        return $this->sum;
    }

    public function setSum(float $sum): static
    {
        $this->sum = $sum;
        return $this;
    }

    public function getSumUpper(): float
    {
        return $this->sumUpper;
    }

    public function setSumUpper(float $sumUpper): static
    {
        $this->sumUpper = $sumUpper;
        return $this;
    }

    public function getSumLower(): float
    {
        return $this->sumLower;
    }

    public function setSumLower(float $sumLower): static
    {
        $this->sumLower = $sumLower;
        return $this;
    }

    public function getStdDev(): float
    {
        return $this->stdDev;
    }

    public function setStdDev(float $stdDev): static
    {
        $this->stdDev = $stdDev;
        return $this;
    }

    public function getEma(): float
    {
        return $this->ema;
    }

    public function setEma(float $ema): static
    {
        $this->ema = $ema;
        return $this;
    }

    public function getEmaLower(): float
    {
        return $this->emaLower;
    }

    public function setEmaLower(float $emaLower): static
    {
        $this->emaLower = $emaLower;
        return $this;
    }

    public function getEmaUpper(): float
    {
        return $this->emaUpper;
    }

    public function setEmaUpper(float $emaUpper): static
    {
        $this->emaUpper = $emaUpper;
        return $this;
    }

    public function calcIndicator(): float
    {
        $period = $this->getPeriod();

        if(
            !$this->getPrevEntity()
            || $period > ($this->getKline()->getRunIndex() + 1)
        ) {
            return $this->stdDev = 0.0;
        }

        $stdDevSum = 0.0;
        $avg = $this->getAvg();

        foreach ($this->getLastPrices() as $price) {
            $stdDevSum += ($price - $avg) ** 2;
        }

        return $this->stdDev = sqrt($stdDevSum / $period);
    }
}
