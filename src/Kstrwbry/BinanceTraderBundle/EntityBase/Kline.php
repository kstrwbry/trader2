<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\EntityBase;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineRawInterface;
use App\Kstrwbry\BinanceTraderBundle\Trait\IdTrait;
use Doctrine\ORM\Mapping as ORM;

abstract class Kline implements KlineInterface
{
    use IdTrait;

    #[ORM\OneToOne(targetEntity: KlineRawInterface::class, cascade: ['persist'], fetch: 'LAZY')]
    protected KlineRawInterface|null $raw = null;

    #[ORM\OneToOne(targetEntity: KlineInterface::class, cascade: ['persist'], fetch: 'LAZY')]
    protected KlineInterface|null $prev = null;

    #[ORM\Column(name:'close', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $close;

    #[ORM\Column(name:'prev_close', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $prevClose;

    #[ORM\Column(name:'close_upper', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $closeUpper;

    #[ORM\Column(name:'close_lower', type:'float', nullable:false, options:['unsigned' => true])]
    protected readonly float $closeLower;

    #[ORM\Column(name:'diff', type:'float', nullable:false, options:['default' => 0])]
    protected readonly float $diff;

    #[ORM\Column(name:'gain', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $gain;

    #[ORM\Column(name:'loss', type:'float', nullable:false, options:['default' => 0, 'unsigned' => true])]
    protected readonly float $loss;

    public function __construct(
        KlineRawInterface   $raw,
        KlineInterface|null $prev
    ) {
        $this->raw  = $raw;
        $this->prev = $prev;

        $this->close = $raw->getClose();
        $this->prevClose = $prev ? $prev->getClose() : 0.0;

        $this->calcDiff();

        $this->closeUpper = $this->gain > 0 ? $this->close : 0.0;
        $this->closeLower = $this->loss > 0 ? $this->close : 0.0;
    }

    private function calcDiff(): void
    {
        $diff = $this->prev
            ? $this->close - $this->prevClose
            : 0.0
        ;

        $this->diff = $diff;
        $this->gain = $diff > 0.0 ? abs($diff) : 0.0;
        $this->loss = $diff < 0.0 ? abs($diff) : 0.0;
    }

    public function getDiff(): float
    {
        return $this->diff;
    }

    public function getGain(): float
    {
        return $this->gain;
    }

    public function getLoss(): float
    {
        return $this->loss;
    }

    public function getPrev(): KlineInterface|null
    {
        return $this->prev;
    }

    public function setPrev(KlineInterface $prev): static
    {
        $this->prev = $prev;

        return $this;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function getPrevClose(): float
    {
        return $this->prevClose;
    }

    public function getRunIndex(): int
    {
        return $this->raw->getRunIndex();
    }
}
