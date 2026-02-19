<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\RviDTO;
use App\Entity\RVI;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RVIInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class RviBuilder extends EntityBuilderBase
{
    /**
     * @var class-string<RVI>
     */
    protected string $entityClass;

    protected DTOInterface|RviDTO $config;

    /**
     * StdDev is an internal dependency of RVI — managed here, not as a top-level indicator.
     */
    private StdDevBuilder $stdDevBuilder;

    public function __construct(
        string      $entityClass,
        DTOInterface $config
    ) {
        if (!$config instanceof RviDTO) {
            throw new \InvalidArgumentException(sprintf(
                'Expected config of type %s, got %s',
                RviDTO::class,
                get_class($config),
            ));
        }

        parent::__construct($entityClass, $config);

        // RVI period also drives the StdDev rolling window size
        $this->stdDevBuilder = new StdDevBuilder($config->getPeriod());
    }

    /**
     * Build and return an RVI entity instance.
     * A StdDev entity is created internally for each kline.
     */
    public function build(
        KlineInterface              $kline,
        IndicatorEntityInterface|null $prevEntity,
    ): RVI {
        $stdDev = $this->stdDevBuilder->build($kline);

        /** @var RVIInterface|null $prevRVI */
        $prevRVI = $prevEntity instanceof RVIInterface ? $prevEntity : null;

        return new RVI(
            $kline,
            $stdDev,
            $prevRVI,
            $this->config->getPeriod(),
            (float) $this->config->getLowerSignalLine(),
            (float) $this->config->getUpperSignalLine(),
        );
    }
}
