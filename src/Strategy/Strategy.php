<?php
declare(strict_types=1);

namespace App\Strategy;

use App\EntityBuilder\EntityBuilderBase;
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
use function strtoupper;

class Strategy
{
    private const INDICATOR_NAMESPACE = '\\App\\Kstrwbry\\BinanceTraderBundle\\Indicator\\';

    private const ENTITY_NAMESPACE = '\\App\\Entity\\';

    private readonly ArrayCollection $klines;

    /**
     * @var array<string, array{
     *     indicator: IndicatorInterface,
     *     builder: EntityBuilderBase,
     *     prevEntity: IndicatorEntityInterface|null
     * }>
     */
    private array $indicators = [];

    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly string               $symbol,
        private readonly EntityBuilderFactory $factory,
        array                                 $config,
    ) {
        $this->klines = new ArrayCollection();
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
    ): void {
        $indicator = $this->createIndicatorFromName($indicatorName);
        $entityFQN = $this->getEntityFQN($indicatorName);
        $builder   = $this->factory->createBuilder($entityFQN, $indicatorConfig);

        $this->indicators[$indicatorName] = [
            'indicator'  => $indicator,
            'builder'    => $builder,
            'prevEntity' => null,
        ];
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
     * @return IndicatorEntityInterface[] All indicator entities built for this kline,
     *                                   ready to be persisted by the caller.
     */
    public function addKline(KlineInterface $kline): array
    {
        $this->klines->add($kline);

        $built = [];

        foreach ($this->indicators as $indicatorName => &$indicatorData) {
            /** @var IndicatorInterface $indicator */
            $indicator = $indicatorData['indicator'];
            /** @var EntityBuilderBase $builder */
            $builder   = $indicatorData['builder'];

            // 1. Build the entity (constructor only — no calculation yet).
            $entity = $builder->build($kline, $indicatorData['prevEntity']);

            // 2. Hand it to the Indicator service: this populates intermediate values
            //    (e.g. EMA sums, gain/loss accumulators) that the entity's own
            //    calcIndicator() then reads.
            $indicator->add($entity);

            // 3. Let the entity finalise its own indicator value.
            //    This is the authoritative "calculate & store" call: after this the
            //    entity's persisted columns (rsi, rvi, cross, …) are populated.
            $entity->calcIndicator();

            $indicatorData['prevEntity'] = $entity;
            $built[] = $entity;
        }

        return $built;
    }

    private function createIndicatorFromName(string $indicatorName): IndicatorInterface
    {
        $indicatorFQN = $this->getIndicatorFQN($indicatorName);

        if (!class_exists($indicatorFQN)) {
            throw new InvalidConfigurationException(sprintf(
                'Trader strategy for symbol "%s" has no valid indicator "%s" (class "%s" not found)',
                $this->symbol,
                $indicatorName,
                $indicatorFQN,
            ));
        }

        if (!is_a($indicatorFQN, IndicatorInterface::class, true)) {
            throw new InvalidConfigurationException(sprintf(
                'Trader strategy for symbol "%s": class "%s" does not implement IndicatorInterface',
                $this->symbol,
                $indicatorFQN,
            ));
        }

        return new $indicatorFQN(new ArrayCollection());
    }

    /**
     * @return class-string<IndicatorInterface>
     */
    private function getIndicatorFQN(string $indicatorName): string
    {
        // YAML keys are lowercase ('macd', 'rvi'); class names are uppercase ('MACD', 'RVI').
        return static::INDICATOR_NAMESPACE . strtoupper($indicatorName);
    }

    /**
     * @return class-string<IndicatorEntityInterface>
     */
    private function getEntityFQN(string $entityName): string
    {
        return static::ENTITY_NAMESPACE . strtoupper($entityName);
    }
}
