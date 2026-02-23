<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\Entity\Kline;
use App\Entity\KlineRaw;

class KlineBuilder
{
    public function build(KlineRaw $klineRaw, ?Kline $lastKline = null): Kline
    {
        return new Kline($klineRaw, $lastKline);
    }
}
