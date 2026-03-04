<?php
declare(strict_types=1);

namespace App\Logger;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\KlineBuilder;
use App\EntityBuilder\KlineRawBuilder;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Strategy\Strategy;
use Doctrine\ORM\EntityManagerInterface;

class KlineLogger
{
    /** @var array<Strategy> */
    private array $strategies = [];

    /** @var array<KlineInterface> */
    private array $klines;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KlineBuilder $klineBuilder,
        private readonly KlineRawBuilder $klineRawBuilder,
    ) {}

    public function logKline(
        KlinerawDTO $klinerawDTO,
        Strategy    $strategy,
        bool        $doFlush = true,
    ): void {
        #print_r(($klinerawDTO->getIsClosedBool() ? 'LOG: ' : 'TMP: ') .  json_encode($klinerawDTO->__serialize()) . PHP_EOL . PHP_EOL);
        if(false === $klinerawDTO->getIsClosedBool()) {
            return;
        }

        $this->strategies[$klinerawDTO->getSymbol()] ??= $strategy;

        #$this->logger->notice(sprintf(
        #    "symbol: %s\ndata: %s",
        #    $klinerawDTO->getSymbol(),
        #    print_r($klinerawDTO->__serialize(), true)
        #));

        $raw = $this->klineRawBuilder->build($klinerawDTO);
        $this->klines[] = $kline = $this->klineBuilder->build($raw, $strategy->getKline());

        $this->em->persist($raw);
        $this->em->persist($kline);

        foreach($strategy->addKline($kline) as $indicatorEntity) {
            $this->em->persist($indicatorEntity);
        }

        if ($klinerawDTO->getRunIndex() > 100) {
            array_shift($this->klines)?->setPrev(null);
        }

        if ($doFlush) {
            $this->em->flush();
        }
    }
}
