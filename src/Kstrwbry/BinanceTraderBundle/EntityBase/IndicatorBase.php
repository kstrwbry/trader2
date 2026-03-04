<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorBaseInterface;

abstract class IndicatorBase implements IndicatorBaseInterface
{
    use IndicatorEntityTrait;

    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['default' => 14, 'unsigned' => true])]
    protected readonly int $period;
    #[ORM\Column(name:'avg', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $avg;

    #[ORM\Column(name:'cma', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $cma;
    #[ORM\Column(name:'ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $ema;
    #[ORM\Column(name:'sma', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $sma;

    #[ORM\Column(name:'sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $sum;
    #[ORM\Column(name:'sum_upper', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $sumUpper;
    #[ORM\Column(name:'sum_lower', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $sumLower;

    public function __construct(
        IndicatorBaseInterface|null $prevEntity,

        float                       $close,
        int                         $period,

        float                       $sum,
        float                       $avg,
        float                       $sumUpper,
        float                       $sumLower,

        float                       $cma,
        float $ema,
        float $sma,
    ) {
        $this->prevEntity   = $prevEntity;
        $this->prevEntityId = $prevEntity?->getId();

        $this->close  = $close;
        $this->period = $period;

        $this->sum = $sum;
        $this->avg = $avg;
        $this->sumUpper = $sumUpper;
        $this->sumLower = $sumLower;

        $this->cma = $cma;
        $this->ema = $ema;
        $this->sma = $sma;
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

    public function getCma(): float
    {
        return $this->cma;
    }

    public function getEma(): float
    {
        return $this->ema;
    }

    public function getSma(): float
    {
        return $this->sma;
    }

    public function getSum(): float
    {
        return $this->sum;
    }

    public function getSumUpper(): float
    {
        return $this->sumUpper;
    }

    public function getSumLower(): float
    {
        return $this->sumLower;
    }
}
