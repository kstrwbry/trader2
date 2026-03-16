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
        int               $period = 14,
    );

    public function setGainSum(float $gainSum): static;

    public function setLossSum(float $lossSum): static;

    public function getGainSum(): float;

    public function getLossSum(): float;

    public function getAvgGain(): float;

    public function setAvgGain(float $avgGain): static;

    public function getAvgLoss(): float;

    public function setAvgLoss(float $avgLoss): static;

    public function getRs(): float;

    public function getRSI(): float;
}
