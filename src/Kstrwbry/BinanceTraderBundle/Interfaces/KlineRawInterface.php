<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface KlineRawInterface
{
    public function getClose(): float;

    public function isClosed(): bool;

    public function getRunIndex(): int;
}
