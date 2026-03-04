<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\Entity\Kline;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineInterface;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\KlineRawInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class KlineBuilder
{
    private Connection $connection;

    private string $sequenceName;

    public function __construct(
        EntityManagerInterface $em,
    ) {
        $this->connection = $em->getConnection();

        $metadata = $em->getClassMetadata(Kline::class);
        $this->sequenceName = sprintf(
            '%s_%s_seq',
            $metadata->getTableName(),
            $metadata->getSingleIdentifierColumnName(),
        );
    }

    public function build(KlineRawInterface $klineRaw, ?KlineInterface $lastKline = null): KlineInterface
    {
        return new Kline(
            $klineRaw->isClosed() ? $this->getNextId() : 0,
            $klineRaw,
            $lastKline,
        );
    }

    final protected function getNextId(): int
    {
        return $this->connection->fetchOne(sprintf(
            'SELECT NEXTVAL(\'%s\')',
            $this->sequenceName,
        ));
    }
}
