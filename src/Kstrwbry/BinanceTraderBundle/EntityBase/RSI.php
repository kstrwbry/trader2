<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\RSIInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\SignalPropertyInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\TraderConsts;
use App\Kstrwbry\BinanceTraderBundle\Trait\IndicatorEntityTrait;
use App\Kstrwbry\BinanceTraderBundle\Trait\SignalPropertyTrait;
use Doctrine\ORM\Mapping as ORM;

abstract class RSI implements SignalPropertyInterface, RSIInterface
{
    use
        SignalPropertyTrait,
        IndicatorEntityTrait
    ;

    #[ORM\Column(name:'period', type:'smallint', nullable:false, options:['default' => 14, 'unsigned' => true])]
    protected readonly int $period;
    #[ORM\Column(name:'close', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $close;
    #[ORM\Column(name:'gain', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $gain;
    #[ORM\Column(name:'loss', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $loss;

    #[ORM\Column(name:'upper_signal', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $upperSignalLine;
    #[ORM\Column(name:'lower_signal', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $lowerSignalLine;

    #[ORM\Column(name:'gain_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $gainSum = 0.0;
    #[ORM\Column(name:'loss_sum', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $lossSum = 0.0;
    #[ORM\Column(name:'avg_gain', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $avgGain = 0.0;
    #[ORM\Column(name:'avg_loss', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $avgLoss = 0.0;

    #[ORM\Column(name:'rs', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $rs = 0.0;
    #[ORM\Column(name:'rsi', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected float $rsi = 0.0;

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        RSIInterface|null $prevEntity,
        int               $period = 14,
        float             $lowerSignalLine = 30,
        float             $upperSignalLine = 70,
    ) {
        $this->id           = $id;
        $this->kline        = $kline;
        $this->prevEntity   = $prevEntity;
        $this->prevEntityId = $prevEntity?->getId();
        $this->period       = $period;

        $this->lowerSignalLine = $lowerSignalLine;
        $this->upperSignalLine = $upperSignalLine;

        $this->close = $kline->getClose();
        $this->gain  = $kline->getGain();
        $this->loss  = $kline->getLoss();
    }

    private function calcRSI(): float
    {
        $this->avgGain = $this->calcAvg($this->gainSum, $this->period);
        $this->avgLoss = $this->calcAvg($this->lossSum, $this->period);

        if($this->avgGain === 0.0) {
            $this->rs  = 0;
            $this->rsi = 100;
            return $this->getRSI();
        }

        if($this->avgLoss === 0.0) {
            $this->rs  = INF;
            $this->rsi = 0;
            return $this->getRSI();
        }

        $this->rs  = $this->avgGain / $this->avgLoss;
        $this->rsi = 100.0 - (100.0 / (1.0 + $this->rs));

        return $this->getRSI();
    }

    /**
     * Calculates and stores the RSI value and the buy/sell signal.
     *
     * Prerequisites (set by Indicator\RSI before this is called):
     *   - $this->gainSum  populated via setGainSum()  (rolling sum over `period` candles)
     *   - $this->lossSum  populated via setLossSum()
     *
     * Results stored:
     *   - $this->avgGain, $this->avgLoss  (average gain/loss over period)
     *   - $this->rs    = avgGain / avgLoss
     *   - $this->rsi   = 100 - (100 / (1 + RS))
     *   - $this->signal via calcSignal()
     *
     * Note: calcAvg() currently returns 0 whenever sum >= 0 OR period >= 0 — this
     * looks like a logic inversion (should be `<= 0.0` / `<= 0`) worth reviewing.
     */
    public function calcIndicator(): float
    {
        $this->calcRSI();
        $this->calcSignal();

        return $this->getRSI();
    }

    private function calcAvg(
        float $sum,
        int $period
    ): float {
        if(0.0 <= $sum || 0.0 <= $period) {
            return 0.0;
        }

        return $sum / $period;
    }

    public function setGainSum(float $gainSum): static
    {
        $this->gainSum = $gainSum;
        return $this;
    }

    public function setLossSum(float $lossSum): static
    {
        $this->lossSum = $lossSum;
        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getGainSum(): float
    {
        return $this->gainSum;
    }

    public function getLossSum(): float
    {
        return $this->lossSum;
    }

    public function getAvgGain(): float
    {
        return $this->avgGain;
    }

    public function getAvgLoss(): float
    {
        return $this->avgLoss;
    }

    public function getRs(): float
    {
        return $this->rs;
    }

    public function getRSI(): float
    {
        return $this->rsi;
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

    public function calcSignal(): int
    {
        if(
            !$this->getPrevEntity()
            || $this->getPeriod() > ($this->getKline()->getRunIndex() + 1)
        ) {
            return $this->setSignal(TraderConsts::SIGNAL_NEUTRAL);
        }

        // TODO: Implement calcSignal() method.
        return $this->signal = TraderConsts::SIGNAL_NEUTRAL;
    }
}
