<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\RsiDTO;
use App\Entity\RSI;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class RsiBuilder extends EntityBuilderBase
{
    protected DTOInterface|RsiDTO $config;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
    ) {
        $this->validateConfigClass($config, RsiDTO::class);

        parent::__construct($config, $indicatorDependencies);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): IndicatorEntityInterface {
        return new RSI(
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
            (float)$this->config->getLowerSignalLine(),
            (float)$this->config->getUpperSignalLine(),
        );
    }
}
