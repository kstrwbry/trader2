<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;

trait IndicatorTrait #implements IndicatorInterface
{
    protected readonly ArrayCollection $numbers;

    public function add(IndicatorEntityInterface $number): void
    {
        $this->numbers->add($number);

        $index = $this->numbers->indexOf($number);
        $this->calc($number, $index);
    }

    protected function reset(): void {}

    public function bulk(): void
    {
        $this->reset();

        foreach($this->numbers as $index => $number) {
            $this->calc($number, $index);
        }
    }

    abstract protected function calc(IndicatorEntityInterface $number, int $index);
}
