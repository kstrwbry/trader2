<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\EMAInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\SignalPropertyTrait;
use Doctrine\ORM\Mapping as ORM;

abstract class EMA implements SignalPropertyInterface, EMAInterface
{
    use
        SignalPropertyTrait,
        IndicatorEntityTrait
        ;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['unsigned' => true])]
    protected readonly int $period;

    #[ORM\Column(name:'close', type:'float', nullable:false)]
    protected readonly float $close;

    #[ORM\Column(name:'ema', type:'float', nullable:false, options:['default' => 0])]
    protected float $ema = 0.0;

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        EMAInterface|null $prevEntity,
        int               $period = 14,
    ) {
        $this->id           = $id;
        $this->kline        = $kline;
        $this->prevEntity   = $prevEntity;
        $this->prevEntityId = $prevEntity?->getId();

        $this->run    = $kline->getRun();
        $this->period = $period;
        $this->close  = $kline->getClose();
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getEma(): float
    {
        return $this->ema;
    }

    private function setEma(float $ema): void
    {
        $this->ema = $ema;
    }

    private function calcEMAValue(): void
    {
        if(!$this->getPrevEntity()) {
            // first value fallback (seed)
            $this->setEma($this->getClose());
            return;
        }

        $prevEma = $this->getPrevEntity()->getEma();

        $multiplier = 2 / ($this->getPeriod() + 1);
        $ema = ($this->getClose() * $multiplier)
            + ($prevEma * (1 - $multiplier));

        $this->setEma($ema);
    }

    public function calcIndicator(): float
    {
        $this->calcEMAValue();
        return $this->getEma();
    }

    /**
     * Simple price/EMA crossover signal
     */
    public function calcSignal(): int
    {
        /** @var EMAInterface $prev */
        $prev = $this->getPrevEntity();

        if(
            !$prev
            || $this->getPeriod() > ($this->getKline()->getRunIndex() + 1)
        ) {
            $this->cross = TraderConsts::SIGNAL_NEUTRAL;

            return $this->setSignal(TraderConsts::SIGNAL_NEUTRAL);
        }

        $signal = TraderConsts::SIGNAL_NEUTRAL;

        $prevClose = $prev->getClose();
        $prevEma   = $prev->getEma();

        if($prevClose <= $prevEma && $this->getClose() > $this->getEma()) {
            $signal = TraderConsts::SIGNAL_BUY;
        }
        elseif($prevClose >= $prevEma && $this->getClose() < $this->getEma()) {
            $signal = TraderConsts::SIGNAL_SELL;
        }

        $this->signal = $signal;

        $this->cross = $this->getSignal() === TraderConsts::SIGNAL_NEUTRAL
            ? $prev->getCross()
            : $this->getSignal();

        return $this->getSignal();
    }
}
