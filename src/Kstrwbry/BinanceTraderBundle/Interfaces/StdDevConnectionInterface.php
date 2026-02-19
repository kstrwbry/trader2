<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface StdDevConnectionInterface
{
    #public function __construct(StdDevInterface $stdDev);

    public function getStdDev(): StdDevInterface;

    #public function setStdDev(StdDevInterface $stdDev): static;
}
