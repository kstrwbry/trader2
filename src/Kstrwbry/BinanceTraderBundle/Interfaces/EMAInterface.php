<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface EMAInterface extends IndicatorEntityInterface
{
    public const string INDICATOR_NAME = 'EMA';

    public const array INDICATOR_DEPENDENCIES = [];

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        EMAInterface|null $prevEntity,
        int               $period = 14,
    );

    public function getEma(): float;
}
