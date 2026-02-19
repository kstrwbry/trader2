<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface StdDevInterface extends IndicatorEntityInterface
{
    public const ?string INDICATOR_NAME = 'stddev';

    public function __construct(
        KlineInterface       $kline,
        StdDevInterface|null $lastStdDev,
        StdDevInterface|null $outdatedStdDev,
        int                  $period = 14
    );

    public function getPeriod(): int;

    public function getAvg(): float;

    public function setAvg(float $avg): static;

    public function getSum(): float;

    public function setSum(float $sum): static;

    public function getStdDev(): float;

    public function setStdDev(float $stdDev): static;

    public function getEmaLower(): float;

    public function setEmaLower(float $emaLower): static;

    public function getEmaUpper(): float;

    public function setEmaUpper(float $emaUpper): static;
}
