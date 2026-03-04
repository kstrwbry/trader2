<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface RSIInterface extends IndicatorEntityInterface
{
    public const ?string INDICATOR_NAME = 'RSI';

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        RSIInterface|null $prevEntity,
        int               $period = 14
    );

    public function setGainSum(float $gainSum): static;

    public function setLossSum(float $lossSum): static;

    public function getPeriod(): int;

    public function getGainSum(): float;

    public function getLossSum(): float;

    public function getAvgGain(): float;

    public function getAvgLoss(): float;

    public function getRs(): float;

    public function getRSI(): float;
}
