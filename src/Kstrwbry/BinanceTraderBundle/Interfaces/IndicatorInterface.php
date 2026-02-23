<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface IndicatorInterface
{
    public function __construct(
        /** @var $numbers IndicatorEntityInterface[] */
        array $numbers,
    );

    public function add(IndicatorEntityInterface $number): void;

    /**
     * @return null|IndicatorEntityInterface
     */
    public function last(): ?IndicatorEntityInterface;

    public function bulk(): void;
}
