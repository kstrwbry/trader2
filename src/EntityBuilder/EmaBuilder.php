<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\EmaDTO;
use App\Entity\Ema;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\EMAInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;
use Doctrine\ORM\EntityManagerInterface;

class EmaBuilder extends EntityBuilderBase
{
    protected DTOInterface|EmaDTO $config;

    protected string $entityClass = Ema::class;

    public function __construct(
        DTOInterface $config,
        array $indicatorDependencies,
        EntityManagerInterface $em,
    ) {
        $this->validateConfigClass($config, EmaDTO::class);

        parent::__construct( $config, $indicatorDependencies, $em);
    }

    /**
     * {@inheritDoc}
     */
    public function build(
        KlineInterface $kline,
        IndicatorEntityInterface|null $prevEntity,
        array $indicatorDependencies,
    ): EMAInterface {
        return new Ema(
            $this->getNextId($kline->isClosed()),
            $kline,
            $prevEntity,
            $this->config->getPeriod(),
        );
    }
}
