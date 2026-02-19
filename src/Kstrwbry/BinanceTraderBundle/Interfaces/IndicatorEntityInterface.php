<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface IndicatorEntityInterface extends KlineConnectionInterface
{
    public const ?string INDICATOR_NAME = null;

    public const array INDICATOR_DEPENDENCIES = [];

    public function calcIndicator(): float;
}
