<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;

trait IndicatorTrait #implements IndicatorInterface
{
    protected array $numbers;

    public function add(IndicatorEntityInterface $number): void
    {
        $this->numbers[] = $number;

        $index = count($this->numbers) - 1;
        $this->calc($number, $index);
    }

    /**
     * @return null|IndicatorEntityInterface
     */
    public function last(): ?IndicatorEntityInterface
    {
        return end($this->numbers) ?: null;
    }

    public function shift(): ?IndicatorEntityInterface
    {
        return array_shift($this->numbers);
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
