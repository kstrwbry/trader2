<?php
declare(strict_types=1);

namespace App\Command;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\EntityBuilderFactory;
use App\Logger\KlineLogger;
use App\Strategy\Strategy;
use Binance\API as BinanceAPI;
use DateTime;
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

        foreach($symbols as $symbol) {
            $this->symbolIndex[$symbol] = 0;

            // Initialise one Strategy per symbol — this sets up all configured
            // indicators (EntityBuilders + Indicator services) for the stream.
            $strategy = new Strategy(
                $this->logger,
                $symbol,
                $this->entityBuilderFactory,
                $strategyConfig['indicators'],
            );

            $klineLogger = new KlineLogger($this->em, $this->logger);

            $this->binanceApiBlank->kline(
                $symbol,
                '1m',
                fn(BinanceAPI $api, string $symbol, mixed $chart) => $klineLogger->logKline(
                    $this->mapChartAndSymbolToKlinerawDto(
                        (array)$chart,
                        $this->symbolIndex[$symbol]++,
                    ),
                    $strategy,
                ),
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

        if(empty($strategies)) {
            throw new \RuntimeException(
                'No trader_strategy entries found in config/packages/binance-trader.yaml.'
            );
        }

        // Return the first configured strategy.
        return reset($strategies);
    }

    private function throwUnlessAllSymbolsAreValid(array $mySymbols): void
    {
        if(0 === count($mySymbols)) {
            throw new InvalidOptionException('Console option "--symbols" must not be empty.');
        }

        $apiSymbols = $this->getAPISymbols();

        $invalidSymbols = array_diff($mySymbols, $apiSymbols);

        if(0 === count($invalidSymbols)) {
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

    private function mapChartAndSymbolToKlinerawDto(array $chart, int $runIndex): KlinerawDTO
    {
        $chart = array_map(
            static fn($chartValue): string => (string)$chartValue,
            $chart,
        );

        return (new KlinerawDTO())
            ->setStartTime($chart['t'])
            ->setStartTimeDate($this->timestampToDate($chart['t']))
            ->setCloseTime($chart['T'])
            ->setCloseTimeDate($this->timestampToDate($chart['T']))
            ->setSymbol($chart['s'])
            ->setInterval($chart['i'])
            ->setFirstTradeID($chart['f'])
            ->setLastTradeID($chart['L'])
            ->setOpen($chart['o'])
            ->setOpenFloat((float)$chart['o'])
            ->setClose($chart['c'])
            ->setCloseFloat((float)$chart['c'])
            ->setHigh($chart['h'])
            ->setHighFloat((float)$chart['h'])
            ->setLow($chart['l'])
            ->setLowFloat((float)$chart['l'])
            ->setBaseAssetVolume($chart['v'])
            ->setBaseAssetVolumeFloat((float)$chart['v'])
            ->setTradesCount($chart['n'])
            ->setTradesCountInt((int)$chart['n'])
            ->setIsClosed($chart['x'])
            ->setIsClosedBool('1' === $chart['x'])
            ->setQuoteAssetVolume($chart['q'])
            ->setQuoteAssetVolumeFloat((float)$chart['q'])
            ->setTakerBuyBaseAssetVolume($chart['V'])
            ->setTakerBuyBaseAssetVolumeFloat((float)$chart['V'])
            ->setTakerBuyQuoteAssetVolume($chart['Q'])
            ->setTakerBuyQuoteAssetVolumeFloat((float)$chart['Q'])
            ->setRunIndex($runIndex);
    }

    private function timestampToDate(int|string $timestamp): DateTime
    {
        $seconds = substr((string)$timestamp, 0, 10);

        return (new DateTime())->setTimestamp((int)$seconds);
    }
}
