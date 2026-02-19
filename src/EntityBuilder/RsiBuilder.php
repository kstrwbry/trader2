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
    /**
     * @var class-string<RSI>
     */
    protected string $entityClass;

    protected DTOInterface|RsiDTO $config;

    public function __construct(
        string       $entityClass,
        DTOInterface $config
    ) {
        if (!$config instanceof RsiDTO) {
            throw new \InvalidArgumentException(sprintf(
                'Expected config of type %s, got %s',
                RsiDTO::class,
                get_class($config),
            ));
        }

        parent::__construct($entityClass, $config);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface              $kline,
        IndicatorEntityInterface|null $prevEntity,
    ): IndicatorEntityInterface {
        return new RSI(
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
            (float) $this->config->getLowerSignalLine(),
            (float) $this->config->getUpperSignalLine(),
        );
    }
}
