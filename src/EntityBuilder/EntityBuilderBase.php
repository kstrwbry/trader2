<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\IndicatorDTO;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

abstract class EntityBuilderBase
{
    protected DTOInterface $config;
    /** @var array<IndicatorEntityInterface> */
    protected array $indicatorDependencies;

    /**
     * @param DTOInterface $config
     * @param array<IndicatorEntityInterface> $indicatorDependencies
     */
    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
    ) {
        $this->config = $config;
        $this->indicatorDependencies = $indicatorDependencies;
    }

    /**
     * @param KlineInterface $kline
     * @param IndicatorEntityInterface|null $prevEntity
     * @param array<IndicatorDTO> $indicatorDependencies
     *
     * @return IndicatorEntityInterface
     */
    abstract public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): IndicatorEntityInterface;

    /**
     * @param DTOInterface $config
     * @param class-string<DTOInterface> $expectedConfigClassName
     * @return void
     */
    protected function validateConfigClass(DTOInterface $config, string $expectedConfigClassName): void
    {
        if (!$config instanceof $expectedConfigClassName) {
            throw new \InvalidArgumentException(sprintf(
                'Expected config of type %s, got %s',
                $expectedConfigClassName,
                get_class($config),
            ));
        }
    }
}
