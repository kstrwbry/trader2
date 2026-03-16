<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface ATRInterface extends IndicatorEntityInterface
{
    public function __construct(
        int               $id,
        KlineInterface    $kline,
        ATRInterface|null $prevEntity,
        int               $period = 14,
    );

    public function getHigh(): float;
    public function getLow(): float;
    public function getTr(): float;
    public function setTr(float $tr): void;
    public function getAtr(): float;
    public function setAtr(float $atr): void;
    public function getAtrSum(): float;
    public function setAtrSum(float $atrSum): void;
}
