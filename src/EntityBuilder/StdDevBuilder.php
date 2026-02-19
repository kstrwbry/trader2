<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\StddevDTO;
use App\Entity\StdDev;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

/**
 * Stateful internal builder for StdDev entities.
 * Maintains the sliding window of StdDev entities required for the rolling calculation.
 *
 * Not registered as a standalone EntityBuilder — instantiated internally by RviBuilder.
 */
class StdDevBuilder extends EntityBuilderBase
{
    protected DTOInterface|StddevDTO $config;

    /** @var array<StdDev> */
    private array $arrStdDev = [];

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
    ) {
        $this->validateConfigClass($config, StddevDTO::class);

        parent::__construct($config, $indicatorDependencies);
    }

    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): StdDevInterface {
        // Replicate the pattern from FetchBinanceDataCommand:
        //   lastStdDev  = current last element (before any shift)
        //   outdatedStdDev = first element shifted out once window is full
        $lastStdDev = !empty($this->arrStdDev) ? end($this->arrStdDev) : null;

        $outdatedStdDev = null;
        if (count($this->arrStdDev) >= $this->config->getPeriod()) {
            $outdatedStdDev = array_shift($this->arrStdDev);
        }

        $stdDev = new StdDev($kline, $lastStdDev, $outdatedStdDev, $this->config->getPeriod());

        $this->arrStdDev[] = $stdDev;

        return $stdDev;
    }
}
