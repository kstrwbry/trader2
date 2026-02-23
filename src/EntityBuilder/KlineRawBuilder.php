<?php
declare(strict_types=1);

namespace App\EntityBuilder;

use App\DTO\KlinerawDTO;
use App\Entity\KlineRaw;

class KlineRawBuilder
{
    public function build(KlinerawDTO $klinerawDTO): KlineRaw
    {
        return new KlineRaw($klinerawDTO);
    }
}
