<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\MACDInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Kstrwbry\BinanceTraderBundle\Trait\IdTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\SignalPropertyTrait;
use Doctrine\ORM\Mapping as ORM;

abstract class MACD implements SignalPropertyInterface, MACDInterface
{
    use
        IdTrait,
        SignalPropertyTrait,
        IndicatorEntityTrait
    ;

    #[ORM\OneToOne(targetEntity: MACDInterface::class, cascade: ['persist'], fetch: 'LAZY')]
    protected MACDInterface|null $prevEntity = null;

    public function getPrevEntity(): MACDInterface|null
    {
        return $this->prevEntity;
    }

    #[ORM\Column(name:'short_ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $shortEMA;
    #[ORM\Column(name:'long_ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $longEMA;
    #[ORM\Column(name:'signal_ema', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $signalEMA;
    #[ORM\Column(name:'macd', type:'float', nullable:false, options:['default' => 0])]
    protected readonly float $macd;

    #[ORM\Column(name:'short_period', type:'smallint', nullable:false, options:['default' => 12, 'unsigned' => true])]
    protected readonly int $shortPeriod;
    #[ORM\Column(name:'long_period', type:'smallint', nullable:false, options:['default' => 26, 'unsigned' => true])]
    protected readonly int $longPeriod;
    #[ORM\Column(name:'signal_period', type:'smallint', nullable:false, options:['default' => 9, 'unsigned' => true])]
    protected readonly int $signalPeriod;
    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;
    #[ORM\Column(name:'"cross"', type:'signal', nullable:false, options:['default' => 0])]
    protected int $cross = 0;

    public function __construct(
        KlineInterface     $kline,
        MACDInterface|null $prevEntity,
        int                $shortPeriod = 12,
        int                $longPeriod = 26,
        int                $signalPeriod = 9,
    ) {
        $this->kline       = $kline;
        $this->prevEntity  = $prevEntity;
        $this->shortPeriod = $shortPeriod;
        $this->longPeriod  = $longPeriod;
        $this->signalPeriod  = $signalPeriod;
        $this->close       = $kline->getClose();
    }

    public function getShortPeriod(): int
    {
        return $this->shortPeriod;
    }

    public function getLongPeriod(): int
    {
        return $this->longPeriod;
    }

    public function getSignalPeriod(): int
    {
        return $this->signalPeriod;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getMacd(): float
    {
        return $this->macd;
    }

    public function setMacd(float $macd): static
    {
        $this->macd = $macd;
        return $this;
    }

    public function getShortEMA(): float
    {
        return $this->shortEMA;
    }

    public function setShortEMA(float $shortEMA): static
    {
        $this->shortEMA = $shortEMA;
        return $this;
    }

    public function getLongEMA(): float
    {
        return $this->longEMA;
    }

    public function setLongEMA(float $longEMA): static
    {
        $this->longEMA = $longEMA;
        return $this;
    }

    public function getSignalEMA(): float
    {
        return $this->signalEMA;
    }

    public function setSignalEMA(float $signalEMA): static
    {
        $this->signalEMA = $signalEMA;
        return $this;
    }

    public function getCross(): int
    {
        return $this->cross;
    }

    public function getPeriod(): int
    {
        return $this->getLongPeriod();
    }

    /**
     * Calculates and stores the EMA crossover state.
     *
     * Prerequisites (set by Indicator\MACD before this is called):
     *   - $this->shortEMA  populated via setShortEMA()
     *   - $this->longEMA   populated via setLongEMA()
     *
     * Result stored: $this->cross  (-1 = long > short, 0 = equal, 1 = short > long)
     *
     * Note: calcSignal() is called separately by Indicator\MACD::calcSignal() and
     * reads the cross value, so it must be called after this method or after
     * Indicator\MACD has already set the cross for the previous entity.
     */
    public function calcIndicator(): float
    {
        return $this->cross = $this->macd <=> $this->getSignalEMA();
    }

    public function calcSignal(): int
    {
        if(
            !$this->getPrevEntity()
            || $this->getShortPeriod() > ($this->getKline()->getRunIndex() + 1)
            || $this->getLongPeriod() > ($this->getKline()->getRunIndex() + 1)
            || $this->getSignalPeriod() > ($this->getKline()->getRunIndex() + 1)
        ) {
            return $this->setSignal(TraderConsts::SIGNAL_NEUTRAL);
        }

        $prevCross = $this->getPrevEntity()->getCross();
        $this->cross = $this->macd <=> $this->getSignalEMA();

        $signal = TraderConsts::SIGNAL_NEUTRAL;

        if($this->cross === TraderConsts::MACD_OVER_SIGNAL_LINE && ($prevCross === TraderConsts::MACD_EVEN || $prevCross === TraderConsts::MACD_UNDER_SIGNAL_LINE)) {
            $signal = TraderConsts::SIGNAL_BUY;
        }

        if($this->cross === TraderConsts::MACD_UNDER_SIGNAL_LINE && ($prevCross === TraderConsts::MACD_EVEN || $prevCross === TraderConsts::MACD_OVER_SIGNAL_LINE)) {
            $signal = TraderConsts::SIGNAL_SELL;
        }

        #print_r('macd: ' . $this->macd . ' | signal line: ' . $this->getSignalEMA() . ' | prevCross: ' . $prevCross . ' | cross: ' . $this->cross . ' | signal: ' . $signal . PHP_EOL);

        return $this->setSignal($signal);
    }
}
