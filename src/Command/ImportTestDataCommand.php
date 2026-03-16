<?php
declare(strict_types=1);

namespace App\Command;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\EntityBuilderFactory;
use App\Logger\KlineLogger;
use App\Strategy\Strategy;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function json_decode;

//TODO: CRON JOB
#[AsCommand(name: 'binance:import:testdata')]
class ImportTestDataCommand extends Command
{
    /** Running kline index per symbol, used as the KlineRaw sequence number. */
    private array $symbolIndex = [];

    protected array $outdatedEntities = [];

    private readonly string $run;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntityBuilderFactory   $entityBuilderFactory,
        private readonly KlineLogger            $klineLogger,
        private readonly array                  $traderConfig,
    ) {
        $this->run = uniqid('run_');

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
        $strategies = [];
        $strategyConfig = current($this->traderConfig['trader_strategy']);
        $bulkFlush = 500;
        $file = new SplFileObject('testdata.ndjson');

        for ($index = 0; !$file->eof(); $index++) {
            $line = trim($file->fgets());
            if ($line === '') {
                continue;
            }

            $chart = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $symbol = $chart['s'];

            $this->symbolIndex[$symbol] ??= 0;

            $strategy = $strategies[$symbol] ??= new Strategy(
                $symbol,
                $this->entityBuilderFactory,
                $strategyConfig['indicators'],
            );

            $this->klineLogger->logKline(
                $this->mapChartAndSymbolToKlinerawDto(
                    (array)$chart,
                    $this->symbolIndex[$symbol]++,
                ),
                $strategy,
                false,
            );

            foreach($strategy->getIndicators() as $indicator) {
                $entity = $indicator->getOutdatedEntity()?->getPrevEntity();

                if($entity) {
                    $this->outdatedEntities[] = $entity;
                }
            }

            if($index > $bulkFlush) {
                $this->outdatedEntities[] = $strategy->getKline();
            }

            if($index > 1 && $index % $bulkFlush === 0) {
                $this->flush();
                $this->clear($index);
                //return Command::SUCCESS;
            }
        }


        if(($index+1) % $bulkFlush === 0) {
            $this->flush();
            $this->clear($index);
        }

        return Command::SUCCESS;
    }

    protected function flush(): void
    {
        $this->em->flush();
    }

    protected function clear(int $index): void
    {
        $this->em->clear();

        foreach($this->outdatedEntities as $entity) {
            unset($entity);
        }

        $this->outdatedEntities = [];
        $this->notice($index);
    }

    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, 2, '.', '') . ' ' . $units[$i];
    }

    private function notice(int $index): void
    {
        $runtime = str_pad(
            number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2, '.', ''),
            7,
            ' ',
            STR_PAD_LEFT,
        );

        print_r(sprintf(
            'peak: %s | mem: %s | index: %s | runtime: %s s',
            $this->formatBytes(memory_get_peak_usage()),
            $this->formatBytes(memory_get_usage()),
            str_pad((string)$index, 7, ' ', STR_PAD_LEFT),
            $runtime,
        ) . PHP_EOL);
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
            ->setRunIndex($runIndex)
            ->setRun($this->run)
        ;
    }

    private function timestampToDate(int|string $timestamp): DateTime
    {
        $seconds = substr((string)$timestamp, 0, 10);

        return new DateTime()->setTimestamp((int)$seconds);
    }
}
