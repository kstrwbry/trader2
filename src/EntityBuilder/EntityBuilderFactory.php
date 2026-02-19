<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\MacdDTO;
use App\DTO\RsiDTO;
use App\DTO\RviDTO;
use App\Kstrwbry\BinanceTraderBundle\EntityBase\MACD as MACDBase;
use App\Kstrwbry\BinanceTraderBundle\EntityBase\RSI as RSIBase;
use App\Kstrwbry\BinanceTraderBundle\EntityBase\RVI as RVIBase;
use App\Kstrwbry\DtoBundle\Base\DtoBase;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use InvalidArgumentException;

class EntityBuilderFactory
{
    /**
     * Maps EntityBase class → [BuilderClass, DTOClass].
     * Keyed by the *base* class so that concrete App\Entity\* subclasses match via is_a().
     *
     * @var array<class-string, array{0: class-string<EntityBuilderBase>, 1: class-string<DtoBase>}>
     */
    protected array $entityBuilders = [
        MACDBase::class => [MacdBuilder::class, MacdDTO::class],
        RSIBase::class  => [RsiBuilder::class,  RsiDTO::class],
        RVIBase::class  => [RviBuilder::class,  RviDTO::class],
    ];

    /**
     * Create the correct builder for the given entity class, hydrating its DTO from
     * the raw config array (e.g. from binance-trader.yaml).
     *
     * @param class-string $entityClass Fully-qualified entity class name
     * @param array        $config      Raw config values (snake_case keys from YAML)
     */
    public function createBuilder(string $entityClass, array $config): EntityBuilderBase
    {
        [$builderClass, $dtoClass] = $this->getBuilderAndDtoClass($entityClass);

        $dto = $this->hydrateDto($dtoClass, $config);

        return new $builderClass($entityClass, $dto);
    }

    /**
     * @return array{0: class-string<EntityBuilderBase>, 1: class-string<DtoBase>}
     */
    private function getBuilderAndDtoClass(string $entityClass): array
    {
        foreach ($this->entityBuilders as $entityBase => [$builderClass, $dtoClass]) {
            if (is_a($entityClass, $entityBase, true)) {
                return [$builderClass, $dtoClass];
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No entity builder registered for entity class: %s',
            $entityClass,
        ));
    }

    /**
     * Hydrate a DTO from a raw config array using DtoBase::__unserialize().
     *
     * @template T of DtoBase
     * @param class-string<T> $dtoClass
     * @return T
     */
    private function hydrateDto(string $dtoClass, array $config): DTOInterface
    {
        /** @var DtoBase $dto */
        $dto = new $dtoClass();
        $dto->__unserialize($config);

        return $dto;
    }
}
