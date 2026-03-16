<?php
declare(strict_types=1);

namespace App\Entity;

use App\Kstrwbry\BinanceTraderBundle\EntityBase\RVI as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indicator_rvi_data')]
#[ORM\Index(name: 'idx_rvi_prev_entity_id', columns: ['prev_entity_id'])]
#[ORM\Index(name: 'idx_rvi_outdated_entity_id', columns: ['outdated_entity_id'])]
final class Rvi extends BaseEntity {}
