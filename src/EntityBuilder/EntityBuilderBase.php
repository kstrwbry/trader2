<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

abstract class EntityBuilderBase
{
    /**
     * @var class-string<IndicatorEntityInterface>
     */
    protected string $entityClass;
    protected DTOInterface $config;

    public function __construct(
        string $entityClass,
        DTOInterface $config
    ) {
        $this->entityClass = $entityClass;
        $this->config = $config;
    }

    /**
     * Build and return an indicator entity instance.
     */
    abstract public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
    ): IndicatorEntityInterface;
}