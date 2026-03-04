<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface RVIInterface extends IndicatorEntityInterface, StdDevConnectionInterface
{
    public const string INDICATOR_NAME = 'RVI';

    public const array INDICATOR_DEPENDENCIES = [StdDevInterface::INDICATOR_NAME];

    public function __construct(
        int               $id,
        KlineInterface    $kline,
        StdDevInterface   $stdDev,
        RVIInterface|null $lastRVI,
        int               $period = 14,
        float             $lowerSignalLine = 30,
        float             $upperSignalLine = 70,
    );

    public function getPeriod(): int;

    public function getUpperSignalLine(): float;

    public function getLowerSignalLine(): float;

    public function getUpperEMASum(): float;
    public function setUpperEMASum(float $upperEMASum): static;

    public function getLowerEMASum(): float;
    public function setLowerEMASum(float $lowerEMASum): static;

    public function getRvi(): float;
    public function setRvi(float $rvi): static;

    public function getUpperEMA(): float;

    public function getLowerEMA(): float;
}
