<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\RviDTO;
use App\Entity\Rvi;
use App\Entity\StdDev;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class RviBuilder extends EntityBuilderBase
{
    protected DTOInterface|RviDTO $config;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
    ) {
        $this->validateConfigClass($config, RviDTO::class);

        parent::__construct( $config, $indicatorDependencies);
    }

    /**
     * {@inheritDoc}
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): Rvi {
        /** @var StdDev $stdDev */
        $stdDev = $indicatorDependencies[StdDevInterface::INDICATOR_NAME]->getIndicator()->last();

        return new Rvi(
            $kline,
            $stdDev,
            $prevEntity,
            $this->config->getPeriod(),
            (float)$this->config->getLowerSignalLine(),
            (float)$this->config->getUpperSignalLine(),
        );
    }
}
