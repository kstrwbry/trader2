<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\RVIInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\SignalPropertyTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Kstrwbry\BinanceTraderBundle\Trait\StdDevConnectionTrait;

abstract class RVI implements SignalPropertyInterface, RVIInterface
{
    use
        SignalPropertyTrait,
        IndicatorEntityTrait,
        StdDevConnectionTrait
    ;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['default' => 14, 'unsigned' => true])]
    protected readonly int $period;
    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;

    #[ORM\Column(name:'upper_signal', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $upperSignalLine;
    #[ORM\Column(name:'lower_signal', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $lowerSignalLine;

    #[ORM\Column(name:'upper_ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $upperEMA;
    #[ORM\Column(name:'lower_ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $lowerEMA;
    #[ORM\Column(name:'upper_ema_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $upperEMASum = 0.0;
    #[ORM\Column(name:'lower_ema_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $lowerEMASum = 0.0;

    #[ORM\Column(name:'rvi', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $rvi = 0.0;

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        StdDevInterface   $stdDev,
        RVIInterface|null $prevEntity,
        int               $period = 14,
        float             $lowerSignalLine = 30,
        float             $upperSignalLine = 70,
    ) {
        $this->id           = $id;
        $this->kline        = $kline;
        $this->stdDev       = $stdDev;
        $this->prevEntity   = $prevEntity;
        $this->prevEntityId = $prevEntity?->getId();

        $this->run    = $kline->getRun();
        $this->period = $period;
        $this->close  = $kline->getClose();

        $this->lowerSignalLine = $lowerSignalLine;
        $this->upperSignalLine = $upperSignalLine;

        $stdDevValue = $this->getStdDev()->getStdDev();
        $prevStdDevValue = $this->getPrevEntity()?->getStdDev()->getStdDev() ?? 0.0;

        $this->upperEMA = $stdDevValue > $prevStdDevValue ? $stdDevValue : 0.0;
        $this->lowerEMA = $stdDevValue < $prevStdDevValue ? $stdDevValue : 0.0;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getUpperSignalLine(): float
    {
        return $this->upperSignalLine;
    }

    public function getLowerSignalLine(): float
    {
        return $this->lowerSignalLine;
    }

    public function getUpperEMASum(): float
    {
        return $this->upperEMASum;
    }

    public function setUpperEMASum(float $upperEMASum): static
    {
        $this->upperEMASum = $upperEMASum;
        return $this;
    }

    public function getLowerEMASum(): float
    {
        return $this->lowerEMASum;
    }

    public function setLowerEMASum(float $lowerEMASum): static
    {
        $this->lowerEMASum = $lowerEMASum;
        return $this;
    }

    public function getRvi(): float
    {
        return $this->rvi;
    }

    public function setRvi(float $rvi): static
    {
        $this->rvi = $rvi;
        return $this;
    }

    public function getUpperEMA(): float
    {
        return $this->upperEMA;
    }

    public function getLowerEMA(): float
    {
        return $this->lowerEMA;
    }

    private function calcRVI(): void
    {
        $upperEMASum = $this->upperEMASum;
        $lowerEMASum = $this->lowerEMASum;
        if($upperEMASum === 0.0) $upperEMASum = 0.001;
        if($lowerEMASum === 0.0) $lowerEMASum = 0.001;

        $rvi = 100 * ($upperEMASum / ($upperEMASum + $lowerEMASum));
        $this->setRvi($rvi);
    }

    public function calcIndicator(): float
    {
        $this->calcRVI();
        $this->calcSignal();

        return $this->getRVI();
    }

    public function calcSignal(): int
    {
        /** @var RVIInterface $prev */
        $prev = $this->getPrevEntity();

        if(
            !$prev
            || $this->getPeriod() > ($this->getKline()->getRunIndex() + 1)
        ) {
            $this->cross = TraderConsts::SIGNAL_NEUTRAL;

            return $this->setSignal(TraderConsts::SIGNAL_NEUTRAL);
        }

        $lastRvi = $prev->getRvi();
        $signal = TraderConsts::SIGNAL_NEUTRAL;

        if(
            $lastRvi < $this->getRvi()
            && $lastRvi <= $this->getLowerSignalLine()
            && $this->getRvi() >= $this->getLowerSignalLine()
        ) {
            $signal = TraderConsts::SIGNAL_BUY;
        }

        if(
            $lastRvi > $this->getRvi()
            && $lastRvi >= $this->getUpperSignalLine()
            && $this->getRvi() <= $this->getUpperSignalLine()
        ) {
            $signal = TraderConsts::SIGNAL_SELL;
        }

        $this->signal = $signal;

        $this->cross = $this->getSignal() === TraderConsts::SIGNAL_NEUTRAL
            ? $prev->getCross()
            : $this->getSignal();

        return $this->getSignal();
    }
}
