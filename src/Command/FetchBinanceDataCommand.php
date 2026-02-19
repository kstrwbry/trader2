<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Kline;
use App\Entity\KlineRaw;
use App\EntityBuilder\EntityBuilderFactory;
use App\Strategy\Strategy;
use Binance\API as BinanceAPI;
use Doctrine\ORM\EntityManagerInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_diff;
use function array_keys;
use function array_map;
use function sprintf;

//TODO: CRON JOB
#[AsCommand(name: 'binance:data:fetch')]
class FetchBinanceDataCommand extends Command
{
    private null|KlineInterface $lastKline = null;

    /**
     * One Strategy instance per symbol, initialised once in execute().
     * The Strategy encapsulates all indicator state (prevEntity, EMA accumulators, …).
     *
     * @var array<string, Strategy>
     */
    private array $strategies = [];

    /** Running kline index per symbol, used as the KlineRaw sequence number. */
    private array $symbolIndex = [];

    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly BinanceAPI             $binanceApiBlank,
        private readonly EntityManagerInterface $em,
        private readonly EntityBuilderFactory   $entityBuilderFactory,
        /**
         * Full processed config from config/packages/binance-trader.yaml.
         * Bound in services.yaml via '%kstrwbry_binance_trader%'.
         * Shape: ['trader_strategy' => ['<name>' => ['stop_loss_condition' => int, 'indicators' => [...]]]]
         */
        private readonly array                  $traderConfig,
    ) {
        parent::__construct();
    }

    /** {@inheritDoc} */
    protected function configure(): void
    {
        $this->addOption(
            'symbols',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'like "ADAUSDT" or/and "BTCBNB"'
        );
    }

    /** {@inheritDoc} */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int {
        $symbols = $input->getOption('symbols');

        $this->throwUnlessAllSymbolsAreValid($symbols);

        // Resolve the indicators config from the first configured strategy.
        // TODO: allow selecting a strategy by name (e.g. via --strategy option).
        $strategyConfig = $this->resolveStrategyConfig();

        foreach ($symbols as $symbol) {
            $this->symbolIndex[$symbol] = 0;

            // Initialise one Strategy per symbol — this sets up all configured
            // indicators (EntityBuilders + Indicator services) for the stream.
            $this->strategies[$symbol] = new Strategy(
                $this->logger,
                $symbol,
                $this->entityBuilderFactory,
                $strategyConfig['indicators'],
            );

            $this->binanceApiBlank->kline(
                $symbol,
                '1m',
                $this->logKline(...),
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Returns the indicators config array for the active strategy.
     * Currently picks the first (and typically only) strategy defined in the YAML.
     *
     * @return array{stop_loss_condition: int, indicators: array}
     */
    private function resolveStrategyConfig(): array
    {
        $strategies = $this->traderConfig['trader_strategy'] ?? [];

        if (empty($strategies)) {
            throw new \RuntimeException(
                'No trader_strategy entries found in config/packages/binance-trader.yaml.'
            );
        }

        // Return the first configured strategy.
        return reset($strategies);
    }

    /** fix arguments because it's used as callback */
    private function logKline(
        BinanceAPI $api,
        string     $symbol,
        mixed      $chart
    ): void {
        $chart = array_map(
            static fn($x): string => (string)$x,
            (array)$chart
        );

        $klineData = [
            'startTime'               => $chart['t'],
            'closeTime'               => $chart['T'],
            'symbol'                  => $chart['s'],
            'interval'                => $chart['i'],
            'firstTradeID'            => $chart['f'],
            'lastTradeID'             => $chart['L'],
            'open'                    => $chart['o'],
            'close'                   => $chart['c'],
            'high'                    => $chart['h'],
            'low'                     => $chart['l'],
            'baseAssetVolume'         => $chart['v'],
            'tradesCount'             => $chart['n'],
            'isClosed'                => $chart['x'],
            'quoteAssetVolume'        => $chart['q'],
            'takerBuyBaseAssetVolume' => $chart['V'],
            'takerBuyQuoteAssetVolume'=> $chart['Q'],
        ];

        if ($klineData['isClosed'] !== '1') {
            return;
        }

        $this->logger->notice(sprintf(
            "symbol: %s\ndata: %s",
            $symbol,
            print_r($klineData, true)
        ));

        // --- Build Kline entities (unchanged — not part of Strategy) ---
        $raw   = new KlineRaw($this->symbolIndex[$symbol], ...$klineData);
        $kline = new Kline($raw, $this->lastKline);

        $this->em->persist($raw);
        $this->em->persist($kline);

        // --- Hand the kline to the Strategy ---
        // addKline() builds every configured indicator entity, runs the Indicator
        // service calculations, calls calcIndicator() on each entity, and returns
        // them all ready for persist.
        $indicatorEntities = $this->strategies[$symbol]->addKline($kline);

        foreach ($indicatorEntities as $indicatorEntity) {
            $this->em->persist($indicatorEntity);
        }

        $this->em->flush();

        // Advance state for next tick.
        $this->lastKline = $kline;
        $this->symbolIndex[$symbol]++;
    }

    private function throwUnlessAllSymbolsAreValid(array $mySymbols): void
    {
        if (0 === count($mySymbols)) {
            throw new InvalidOptionException('Console option "--symbols" must not be empty.');
        }

        $apiSymbols = $this->getAPISymbols();

        $invalidSymbols = array_diff($mySymbols, $apiSymbols);

        if (0 === count($invalidSymbols)) {
            return;
        }

        $wrapSymbols = static fn($symbol): string => sprintf('"%s"', $symbol);

        throw new InvalidOptionException(sprintf(
            "Invalid console option \"--symbols\" values: %s.\nValid symbols: %s",
            implode(', ', array_map($wrapSymbols, $invalidSymbols)),
            implode(', ', array_map($wrapSymbols, $apiSymbols)),
        ));
    }

    private function getAPISymbols(): array
    {
        // TODO: CACHING
        return array_keys($this->binanceApiBlank->prices());
    }
}
