<?php
declare(strict_types=1);

namespace App\Strategy;

use App\DTO\IndicatorDTO;
use App\EntityBuilder\EntityBuilderFactory;
use Doctrine\Common\Collections\ArrayCollection;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function array_walk;
use function class_exists;
use function is_a;

class Strategy
{
    private const INDICATOR_NAMESPACE = '\\App\\Kstrwbry\\BinanceTraderBundle\\Indicator\\';

    private const ENTITY_NAMESPACE = '\\App\\Entity\\';

    /**
     * @var array<string, IndicatorDTO>
     */
    private array $indicators = [];

    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly string               $symbol,
        private readonly EntityBuilderFactory $factory,
        array                                 $config,
    ) {
        $this->init($config);
    }

    private function init(array $config): void
    {
        array_walk($config, $this->initIndicator(...));
    }

    /**
     * array_walk passes (value, key) — indicatorConfig is the value, indicatorName is the key.
     */
    private function initIndicator(
        array  $indicatorConfig,
        string $indicatorName,
    ): IndicatorDTO {
        $entityFQN = $this->getEntityFQN($indicatorName);

        $indicatorDependencies = [];
        foreach($entityFQN::INDICATOR_DEPENDENCIES as $dependentIndicatorName) {
            $indicatorDependencies[$dependentIndicatorName] = $this->initIndicator($indicatorConfig, $dependentIndicatorName);
        }

        $indicatorDTO = $this->indicators[$indicatorName] = new IndicatorDTO()
            ->setIndicatorName($indicatorName)
            ->setIndicator($this->createIndicatorFromName($indicatorName))
            ->setIndicatorConfig($indicatorConfig)
            ->setPrevEntity(null)
            ->setIndicatorDependencies($indicatorDependencies);

        $indicatorDTO->setBuilder($this->factory->createBuilder($entityFQN, $indicatorDTO));

        return $indicatorDTO;
    }

    /**
     * Process a new kline through every configured indicator.
     *
     * For each indicator:
     *   1. The EntityBuilder constructs the entity (passing prevEntity for rolling state).
     *   2. The Indicator service adds the entity to its collection and runs its own
     *      bookkeeping (EMA accumulation, gain/loss sums, etc.) via IndicatorTrait::calc().
     *   3. entity->calcIndicator() is called so the entity finalises its own stored
     *      values (cross, RSI, RVI, …) immediately before being handed back for persist.
     *
     * @return iterable<IndicatorEntityInterface> All indicator entities built for this kline,
     *                                   ready to be persisted by the caller.
     */
    public function addKline(KlineInterface $kline): iterable
    {
        $start = microtime(true);

        foreach($this->indicators as $indicatorName => $indicatorDTO) {
            $startBuildTime = microtime(true);

            $entity = $indicatorDTO->getBuilder()->build(
                $kline,
                $indicatorDTO->getPrevEntity(),
                $indicatorDTO->getIndicatorDependencies(),
            );

            $this->logger->debug(sprintf(
                'Built entity for indicator "%s" in %.2f ms',
                $indicatorName,
                (microtime(true) - $startBuildTime) * 1000,
            ));

            $startCalculateTime = microtime(true);
            $indicatorDTO->getIndicator()->add($entity);

            $entity->calcIndicator();

            $this->logger->debug(sprintf(
                'Calculated indicator "%s" in %.2f ms',
                $indicatorName,
                (microtime(true) - $startCalculateTime) * 1000,
            ));

            $indicatorDTO->setPrevEntity($entity);

            if ($kline->getRunIndex() + 3 > $entity->getPeriod()) {
                $indicatorDTO->getIndicator()->shift();
            }

            yield $entity;
        }
    }

    public function getKline(): ?KlineInterface
    {
        return current($this->indicators)->getPrevEntity()?->getKline();
    }

    private function createIndicatorFromName(string $indicatorName): IndicatorInterface
    {
        $indicatorFQN = $this->getIndicatorFQN($indicatorName);

        if(!class_exists($indicatorFQN)) {
            throw new InvalidConfigurationException(sprintf(
                'Trader strategy for symbol "%s" has no valid indicator "%s" (class "%s" not found)',
                $this->symbol,
                $indicatorName,
                $indicatorFQN,
            ));
        }

        if(!is_a($indicatorFQN, IndicatorInterface::class, true)) {
            throw new InvalidConfigurationException(sprintf(
                'Trader strategy for symbol "%s": class "%s" does not implement IndicatorInterface',
                $this->symbol,
                $indicatorFQN,
            ));
        }

        return new $indicatorFQN([]);
    }

    /**
     * @return class-string<IndicatorInterface>
     */
    private function getIndicatorFQN(string $indicatorName): string
    {
        return static::INDICATOR_NAMESPACE . $indicatorName;
    }

    /**
     * @return class-string<IndicatorEntityInterface>
     */
    private function getEntityFQN(string $entityName): string
    {
        return static::ENTITY_NAMESPACE . $entityName;
    }
}
