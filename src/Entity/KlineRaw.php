<?php
declare(strict_types=1);

namespace App\Entity;

use App\Kstrwbry\BinanceTraderBundle\EntityBase\KlineRaw as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kline_raw_data')]
#[ORM\Index(name: 'idx_run_index', columns: ['run_index'])]
final class KlineRaw extends BaseEntity {}
