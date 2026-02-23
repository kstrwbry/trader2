<?php
declare(strict_types=1);

namespace App\Command;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\EntityBuilderFactory;
use App\Logger\KlineLogger;
use App\Strategy\Strategy;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

//TODO: CRON JOB
#[AsCommand(name: 'binance:import:testdata')]
class ImportTestDataCommand extends Command
{
    /** Running kline index per symbol, used as the KlineRaw sequence number. */
    private array $symbolIndex = [];

    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly EntityManagerInterface $em,
        private readonly EntityBuilderFactory   $entityBuilderFactory,
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
        $symbol = 'ADAUSDT';

        $strategyConfig = current($this->traderConfig['trader_strategy']);

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

        $testdata = json_decode(file_get_contents('testdata.json'), true);

        $bulkFlush = 100;
        $index = 0;
        $flushIndex = 0;

        foreach ($testdata as $index => $chart) {
            $klineLogger->logKline(
                $this->mapChartAndSymbolToKlinerawDto(
                    (array)$chart,
                    $this->symbolIndex[$symbol]++,
                ),
                $strategy,
                false,
            );

            if (($index+1) % $bulkFlush === 0) {
                $flushStartTime = microtime(true);
                $runtime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                $this->logger->info(sprintf('Flushing %d - memory %s - runtime %d', $index, $this->formatBytes(memory_get_usage()), $runtime));
                $this->em->flush();

                if(++$flushIndex % 3 === 0) {
                    $this->logger->notice('Calling entity manager flush');
                    $this->em->clear();
                    gc_collect_cycles();
                }

                $unitOfWorkSizte = $this->em->getUnitOfWork()->size();
                $this->logger->notice(sprintf('UnitOfWork size after flush: %d', $unitOfWorkSizte));

                $this->logger->info(sprintf('Flush completed - flush time %d seconds', microtime(true) - $flushStartTime));
            }
        }


        if (($index+1) % $bulkFlush === 0) {
            $runtime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
            $this->logger->info(sprintf('Flushing %d - memory %s - runtime %d', $index, $this->formatBytes(memory_get_usage()), $runtime));
            $this->em->flush();
            $this->em->clear();
        }

        return Command::SUCCESS;
    }

    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
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

    private function dumpData()
    {
        $fp = fopen('testdata.json', 'rb');

        $chunkSize = 4096;
        $buffer = '';
        $depth = 0;
        $inString = false;
        $escape = false;

        while (!feof($fp)) {
            $buffer .= fread($fp, $chunkSize);

            $start = null;
            $len = strlen($buffer);

            for ($i = 0; $i < $len; $i++) {
                $ch = $buffer[$i];

                if ($inString) {
                    if ($escape) $escape = false;
                    elseif ($ch === '\\') $escape = true;
                    elseif ($ch === '"') $inString = false;
                    continue;
                }

                if ($ch === '"') {
                    $inString = true;
                } elseif ($ch === '{') {
                    if ($depth === 0) $start = $i;
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0 && $start !== null) {
                        $json = substr($buffer, $start, $i - $start + 1);
                        $data = json_decode($json, true);

                        // process dataset
                        var_dump($data);
                        die();

                        $buffer = substr($buffer, $i + 1);
                        $i = -1;
                        $len = strlen($buffer);
                        $start = null;
                    }
                }
            }
        }

        fclose($fp);
    }

    private function timestampToDate(int|string $timestamp): DateTime
    {
        $seconds = substr((string)$timestamp, 0, 10);

        return new DateTime()->setTimestamp((int)$seconds);
    }
}
