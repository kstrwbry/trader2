<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\StddevDTO;
use App\Entity\StdDev;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\ORM\EntityManagerInterface;

class StdDevBuilder extends EntityBuilderBase
{
    protected DTOInterface|StddevDTO $config;

    protected string $entityClass = StdDev::class;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->validateConfigClass($config, StddevDTO::class);

        parent::__construct($config, $indicatorDependencies, $em);
    }

    /**
     * {@inheritDoc}
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): StdDevInterface {
        return new StdDev(
            $this->getNextId($kline->isClosed()),
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
        );
    }
}
