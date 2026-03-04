<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;

trait IndicatorTrait #implements IndicatorInterface
{
    /** @var array<IndicatorEntityInterface> */
    protected array $numbers;

    public function __construct(
        array $numbers,
    ) {
        $this->numbers = $numbers;

        $this->bulk();
    }

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
        $period = $this->last()?->getPeriod();

        if (count($this->numbers) > $period + 3) {
            return array_shift($this->numbers);
        }

        return null;
    }

    public function pop(): ?IndicatorEntityInterface
    {
        return array_pop($this->numbers);
    }

    public function getOutdatedEntity(?int $index = null): ?IndicatorEntityInterface
    {
        $period = $this->last()?->getPeriod();

        if ($period === null) {
            return null;
        }

        $index = $index ?? count($this->numbers) - 1;

        $outdatedIndex = $index - ($period + 1);

        return $this->numbers[$outdatedIndex] ?? null;
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
