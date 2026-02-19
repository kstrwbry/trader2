<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface KlineInterface
{
    public function setPrev(KlineInterface $prev): static;

    public function getPrev(): KlineInterface|null;

    public function getClose(): float;

    public function getPrevClose(): float;

    public function getDiff(): float;

    public function getGain(): float;

    public function getLoss(): float;

    public function getRunIndex(): int;
}
