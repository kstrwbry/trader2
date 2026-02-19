<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\MacdDTO;
use App\Entity\Macd;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class MacdBuilder extends EntityBuilderBase
{
    protected DTOInterface|MacdDTO $config;

    /** {@inheritdoc} */
    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
    ) {
        $this->validateConfigClass($config, MacdDTO::class);

        parent::__construct( $config, $indicatorDependencies);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): IndicatorEntityInterface {
        return new Macd(
            $kline,
            $prevEntity,
            $this->config->getShortPeriod(),
            $this->config->getLongPeriod(),
        );
    }
}
