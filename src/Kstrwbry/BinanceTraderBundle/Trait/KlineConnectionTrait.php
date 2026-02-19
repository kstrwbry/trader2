<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use Doctrine\ORM\Mapping as ORM;

trait KlineConnectionTrait #implements KlineConnectionInterface
{
    #[ORM\OneToOne(targetEntity: KlineInterface::class, cascade: ['persist'])]
    protected readonly KlineInterface $kline;

    public function getKline(): KlineInterface
    {
        return $this->kline;
    }

    public function getClose(): float
    {
        return $this->kline->getClose();
    }
}
