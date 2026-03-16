<?php
declare(strict_types=1);

namespace App\Command;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\EntityBuilderFactory;
use App\Logger\KlineLogger;
use App\Strategy\Strategy;
use App\Trader\Trader;
use Binance\API;
use Binance\API as BinanceAPI;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\PdoStore;

use function array_diff;
use function array_keys;
use function array_map;
use function sprintf;
use function count;
use function implode;

//TODO: CRON JOB
#[AsCommand(name: 'binance:data:fetch')]
class FetchBinanceDataCommand extends Command
{
    /** Running kline index per symbol, used as the KlineRaw sequence number. */
    private array $symbolIndex = [];

    private LockFactory $lockFactory;
    private ?LockInterface $lock = null;
    private bool $noLock;
    private bool $overrideLock;

    public function __construct(
        private readonly BinanceAPI             $binanceApiBlank,
        private readonly API                    $binanceApiLogin,
        private readonly EntityBuilderFactory   $entityBuilderFactory,
        private readonly Trader                 $trader,
        private readonly KlineLogger            $klineLogger,
        private readonly EntityManagerInterface $em,
        private readonly array                  $traderConfig,
    ) {
        parent::__construct();

        $store = new PdoStore($this->em->getConnection()->getNativeConnection());
        $this->lockFactory = new LockFactory($store);
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

        $this->addOption(
            'no-lock',
            null,
            InputOption::VALUE_NONE,
        );

        $this->addOption(
            'override-lock',
            null,
            InputOption::VALUE_NONE,
        );
    }

    /** {@inheritDoc} */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int {
        $symbols = $input->getOption('symbols');
        $this->noLock = $input->getOption('no-lock');
        $this->overrideLock = $input->getOption('override-lock');

        $this->throwUnlessAllSymbolsAreValid($symbols);

        // Resolve the indicators config from the first configured strategy.
        // TODO: allow selecting a strategy by name (e.g. via --strategy option).
        $strategyConfig = $this->resolveStrategyConfig();

        try {
            foreach($symbols as $symbol) {
                if(!$this->refreshLock($symbol)) {
                    return Command::SUCCESS;
                }

                $this->symbolIndex[$symbol] = 0;

                $this->test($symbol);

                // Initialise one Strategy per symbol — this sets up all configured
                // indicators (EntityBuilders + Indicator services) for the stream.
                $strategy = new Strategy(
                    $symbol,
                    $this->entityBuilderFactory,
                    $strategyConfig['indicators'],
                );

                $this->binanceApiBlank->kline(
                    $symbol,
                    '1m',
                    function(BinanceAPI $api, string $symbol, mixed $chart) use ($strategy)
                    {
                        $chart = (array)$chart;

                        if(true === $chart['x']) {
                            $this->symbolIndex[$symbol]++;
                        }

                        $this->kline(
                            $this->mapChartAndSymbolToKlinerawDto(
                                $chart,
                                $this->symbolIndex[$symbol],
                            ),
                            $strategy,
                        );

                        $this->refreshLock($symbol);
                    },
                );
            }
        } finally {
            $this->releaseLock();

            $this->em->clear();
            $this->em->getConnection()->close();
        }

        return Command::SUCCESS;
    }

    private function kline(
        KlinerawDTO $klinerawDTO,
        Strategy    $strategy,
        bool        $doFlush = true,
    ): void {
        $this->klineLogger->logKline(
            $klinerawDTO,
            $strategy,
            $doFlush,
        );

        $this->trader->trade($klinerawDTO, $strategy);
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
            throw new RuntimeException(
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

        return new KlinerawDTO()
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

        return new DateTime()->setTimestamp((int)$seconds);
    }

    private function refreshLock($symbol): bool
    {
        if($this->noLock) {
            return true;
        }

        if($this->lock) {
            $this->lock->refresh();
            return true;
        }

        $this->lock = $this->lockFactory->createLock(
            $this->getName() . $symbol,
            180,
            true
        );

        if(!$this->overrideLock && $this->lock->isAcquired()) {
            return false;
        }

        return $this->lock->acquire();
    }

    private function releaseLock(): void
    {
        $this->lock?->release();
    }

    private function test(string $symbol): void
    {
    }
}
