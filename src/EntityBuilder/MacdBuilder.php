<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\MacdDTO;
use App\Entity\MACD;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class MacdBuilder extends EntityBuilderBase
{
    /**
     * @var class-string<MACD>
     */
    protected string $entityClass;

    protected DTOInterface|MacdDTO $config;

    public function __construct(
        string $entityClass,
        DTOInterface $config
    ) {
        if (!$config instanceof MacdDTO) {
            throw new \InvalidArgumentException(sprintf(
                'Expected config of type %s, got %s',
                MacdDTO::class,
                get_class($config),
            ));
        }

        parent::__construct($entityClass, $config);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
    ): IndicatorEntityInterface {
        return new MACD(
            $kline,
            $prevEntity,
            $this->config->getShortPeriod(),
            $this->config->getLongPeriod(),
        );
    }
}