<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\RsiDTO;
use App\Entity\RSI;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\RSIInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\ORM\EntityManagerInterface;

class RsiBuilder extends EntityBuilderBase
{
    protected DTOInterface|RsiDTO $config;

    protected string $entityClass = RSI::class;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->validateConfigClass($config, RsiDTO::class);

        parent::__construct($config, $indicatorDependencies, $em);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): RSIInterface {
        return new RSI(
            $this->getNextId($kline->isClosed()),
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
            (float)$this->config->getLowerSignalLine(),
            (float)$this->config->getUpperSignalLine(),
        );
    }
}
