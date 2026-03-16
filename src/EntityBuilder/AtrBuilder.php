<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\AtrDTO;
use App\Entity\Atr;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\ATRInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\ORM\EntityManagerInterface;

class AtrBuilder extends EntityBuilderBase
{
    protected DTOInterface|AtrDTO $config;

    protected string $entityClass = Atr::class;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->validateConfigClass($config, AtrDTO::class);

        parent::__construct( $config, $indicatorDependencies, $em);
    }

    /**
     * {@inheritDoc}
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): ATRInterface {
        return new Atr(
            $this->getNextId($kline->isClosed()),
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
        );
    }
}
