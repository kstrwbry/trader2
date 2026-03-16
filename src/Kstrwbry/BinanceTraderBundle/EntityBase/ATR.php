<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\ATRInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\SignalPropertyTrait;
use Doctrine\ORM\Mapping as ORM;

abstract class ATR implements SignalPropertyInterface, ATRInterface
{
    use
        SignalPropertyTrait,
        IndicatorEntityTrait
    ;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['default' => 14, 'unsigned' => true])]
    protected readonly int $period;
    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;
    #[ORM\Column(name:'high', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $high;
    #[ORM\Column(name:'low', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $low;

    #[ORM\Column(name:'tr', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $tr = 0.0;
    #[ORM\Column(name:'atr', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $atr = 0.0;
    #[ORM\Column(name:'atr_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $atrSum = 0.0;

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        ATRInterface|null $prevEntity,
        int               $period = 14,
    ) {
        $this->id           = $id;
        $this->kline        = $kline;
        $this->prevEntity   = $prevEntity;
        $this->prevEntityId = $prevEntity?->getId();

        $this->run    = $kline->getRun();
        $this->period = $period;
        $this->close  = $kline->getClose();
        $this->high   = $kline->getHighFloat();
        $this->low    = $kline->getLowFloat();
    }

    public function getTr(): float
    {
        return $this->tr;
    }

    public function setTr(float $tr): void
    {
        $this->tr = $tr;
    }

    public function getAtr(): float
    {
        return $this->atr;
    }

    public function setAtr(float $atr): void
    {
        $this->atr = $atr;
    }

    public function getAtrSum(): float
    {
        return $this->atrSum;
    }

    public function setAtrSum(float $atrSum): void
    {
        $this->atrSum = $atrSum;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getHigh(): float
    {
        return $this->high;
    }

    public function getLow(): float
    {
        return $this->low;
    }

    private function calcATRValue(): void
    {
        $atrSum = $this->atrSum;
        if($atrSum === 0.0) {
            $atrSum = 0.001; // prevent division by zero
        }

        $atr = $atrSum / $this->getPeriod();
        $this->setAtr($atr);
    }

    public function calcIndicator(): float
    {
        $this->calcATRValue();
        return $this->getAtr();
    }

    /**
     * Optional: simple signal example if ATR used for breakout detection.
     */
    public function calcSignal(float $multiplier = 1.5): int
    {
        if(
            !$this->getPrevEntity()
            || $this->getPeriod() > ($this->getKline()->getRunIndex() + 1)
        ) {
            $this->cross = TraderConsts::SIGNAL_NEUTRAL;

            return $this->setSignal(TraderConsts::SIGNAL_NEUTRAL);
        }

        $signal = TraderConsts::SIGNAL_NEUTRAL;

        $prevClose = $this->getPrevEntity()->getClose();
        $atr = $this->getAtr();

        if($this->getClose() > $prevClose + $atr * $multiplier) {
            $signal = TraderConsts::SIGNAL_BUY;
        } elseif($this->getClose() < $prevClose - $atr * $multiplier) {
            $signal = TraderConsts::SIGNAL_SELL;
        }

        $this->signal = $signal;

        $this->cross = $this->getSignal() === TraderConsts::SIGNAL_NEUTRAL
            ? $this->getPrevEntity()->getCross()
            : $this->getSignal();

        return $this->getSignal();
    }
}
