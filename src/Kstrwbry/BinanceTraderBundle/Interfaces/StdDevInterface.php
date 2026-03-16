<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface StdDevInterface extends IndicatorEntityInterface
{
    public const ?string INDICATOR_NAME = 'StdDev';

    public const string MODE_EMA = 'ema';
    public const string MODE_EMA_LOWER = 'ema_lower';
    public const string MODE_EMA_UPPER = 'ema_upper';

    public const array STD_DEV_MODES = [
        self::MODE_EMA,
        self::MODE_EMA_LOWER,
        self::MODE_EMA_UPPER,
    ];

    public function __construct(
        int                  $id,
        KlineInterface       $kline,
        StdDevInterface|null $prevEntity,
        int                  $period = 14,
    );

    public function getPeriod(): int;

    public function getAvg(): float;

    public function setAvg(float $avg): static;

    public function getLastPrices(): array;

    public function setLastPrices(array $lastPrices): static;

    public function getSum(): float;

    public function setSum(float $sum): static;

    public function getStdDev(): float;

    public function setStdDev(float $stdDev): static;

    public function getEmaLower(): float;

    public function setEmaLower(float $emaLower): static;

    public function getEma(): float;

    public function setEma(float $ema): static;

    public function getEmaUpper(): float;

    public function setEmaUpper(float $emaUpper): static;
}
