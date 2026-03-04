<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface MACDInterface extends IndicatorEntityInterface
{
    public const ?string INDICATOR_NAME = 'MACD';

    public function __construct(
        int                $id,
        KlineInterface     $kline,
        MACDInterface|null $prevEntity,
        int                $shortPeriod = 12,
        int                $longPeriod = 26,
    );

    public function getShortPeriod(): int;

    public function getLongPeriod(): int;

    public function getClose(): float;

    public function getShortEMA(): float;

    public function setShortEMA(float $shortEMA): static;

    public function getLongEMA(): float;

    public function setLongEMA(float $longEMA): static;

    public function getSignalEMA(): float;

    public function setSignalEMA(float $signalEMA): static;
}
