<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\MacdDTO;
use App\Entity\Macd;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\MACDInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\ORM\EntityManagerInterface;

class MacdBuilder extends EntityBuilderBase
{
    protected DTOInterface|MacdDTO $config;

    protected string $entityClass = Macd::class;

    /** {@inheritdoc} */
    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->validateConfigClass($config, MacdDTO::class);

        parent::__construct( $config, $indicatorDependencies, $em);
    }

    /**
     * Build and return an indicator entity instance.
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): MACDInterface {
        return new Macd(
            $this->getNextId($kline->isClosed()),
            $kline,
            $prevEntity,
            $this->config->getShortPeriod(),
            $this->config->getLongPeriod(),
            $this->config->getSignalPeriod(),
        );
    }
}
