<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IdTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity for Standard Deviation (StdDev).
 * Used as a dependency of RVI — not a top-level indicator in the strategy config.
 */
#abstract
class StdDev implements StdDevInterface
{
    use
        IdTrait,
        IndicatorEntityTrait
    ;

    #[ORM\OneToOne(targetEntity: StdDevInterface::class, cascade: ['persist'])]
    protected StdDevInterface|null $prevEntity = null;
    #[ORM\OneToOne(targetEntity: StdDevInterface::class, cascade: ['persist'])]
    protected StdDevInterface|null $outdatedStdDev = null;

    public function getPrevEntity(): StdDevInterface|null
    {
        return $this->prevEntity;
    }

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

    #[ORM\Column(name:'std_dev_sum_upper', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $sumUpper = 0.0;

    #[ORM\Column(name:'std_dev_sum_lower', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $sumLower = 0.0;

    #[ORM\Column(name:'ema_upper', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $emaUpper = 0.0;
    #[ORM\Column(name:'ema_lower', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $emaLower = 0.0;

    public function __construct(
        KlineInterface       $kline,
        StdDevInterface|null $lastStdDev,
        StdDevInterface|null $outdatedStdDev,
        int                  $period = 14
    ) {
        $this->kline          = $kline;
        $this->prevEntity     = $lastStdDev;
        $this->outdatedStdDev = $outdatedStdDev;

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

    /**
     * StdDev's real calculation lives entirely in Indicator\StdDev::calc(), which
     * calls setSum(), setSumUpper(), setSumLower(), setAvg(), and setStdDev() on this
     * entity directly. That Indicator class also calls calcIndicator() internally
     * (before computing the final stdDev value).
     *
     * This entity is built internally by RviBuilder — it is NOT a top-level indicator
     * in the strategy config, so Indicator\StdDev is not used in the current flow.
     * All StdDev columns will therefore hold their default (0) values until a
     * dedicated StdDev strategy entry or an alternative calculation path is wired up.
     */
    public function calcIndicator(): float
    {
        // Calculation is performed by Indicator\StdDev after construction.
        return 0.0;
    }
}
