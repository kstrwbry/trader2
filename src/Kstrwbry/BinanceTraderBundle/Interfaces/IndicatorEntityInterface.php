<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface IndicatorEntityInterface extends IdInterface, KlineConnectionInterface
{
    public const ?string INDICATOR_NAME = null;

    public const array INDICATOR_DEPENDENCIES = [];

    public function calcIndicator(): float;

    public function getPeriod();

    public function getPrevEntityId(): int|null;
    public function getPrevEntity(): IndicatorEntityInterface|null;
    public function setPrevEntity(IndicatorEntityInterface|null $prevEntity): static;

    public function getOutdatedEntityId(): int|null;
    public function getOutdatedEntity(): IndicatorEntityInterface|null;
    public function setOutdatedEntity(IndicatorEntityInterface|null $outdatedEntity): static;
}
