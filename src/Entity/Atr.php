<?php
declare(strict_types=1);

namespace App\Entity;

use App\Kstrwbry\BinanceTraderBundle\EntityBase\ATR as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indicator_atr_data')]
#[ORM\Index(name: 'idx_atr_prev_entity_id', columns: ['prev_entity_id'])]
#[ORM\Index(name: 'idx_atr_outdated_entity_id', columns: ['outdated_entity_id'])]
final class Atr extends BaseEntity {}
