<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface IndicatorBaseInterface
{
    public function getPeriod(): int;
    public function getAvg(): float;
    public function getCma(): float;
    public function getEma(): float;
    public function getSma(): float;
    public function getSum(): float;
    public function getSumUpper(): float;
    public function getSumLower(): float;
}
