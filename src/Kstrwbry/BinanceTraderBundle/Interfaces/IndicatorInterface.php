<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

use Doctrine\Common\Collections\ArrayCollection;

interface IndicatorInterface
{
    public function __construct(
        /** @var ArrayCollection|IndicatorEntityInterface[] */
        ArrayCollection $numbers,
    );

    public function add(IndicatorEntityInterface $number): void;

    public function bulk(): void;
}
