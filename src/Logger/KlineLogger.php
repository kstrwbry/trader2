<?php
declare(strict_types=1);

namespace App\Logger;

use App\DTO\KlinerawDTO;
use App\EntityBuilder\KlineBuilder;
use App\EntityBuilder\KlineRawBuilder;
use App\Strategy\Strategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class KlineLogger
{
    /** @var array<Strategy> */
    private array $strategies = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function logKline(
        KlinerawDTO $klinerawDTO,
        Strategy    $strategy,
        bool        $doFlush = true,
    ): void {
        if(false === $klinerawDTO->getIsClosedBool()) {
            return;
        }

        $this->strategies[$klinerawDTO->getSymbol()] ??= $strategy;

        #$this->logger->notice(sprintf(
        #    "symbol: %s\ndata: %s",
        #    $klinerawDTO->getSymbol(),
        #    print_r($klinerawDTO->__serialize(), true)
        #));

        $raw = new KlineRawBuilder()->build($klinerawDTO);
        $kline = new KlineBuilder()->build($raw, $strategy->getKline());

        $this->em->persist($raw);
        $this->em->persist($kline);

        foreach($strategy->addKline($kline) as $indicatorEntity) {
            $this->em->persist($indicatorEntity);
        }

        if ($doFlush) {
            $this->em->flush();
        }
    }
}
