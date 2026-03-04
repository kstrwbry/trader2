<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle;

use App\Kstrwbry\BaseBundle\KstrwbryBaseBundle;
use App\Kstrwbry\BinanceTraderBundle\DependencyInjection\TraderStrategyConfiguration;

class KstrwbryBinanceTraderBundle extends KstrwbryBaseBundle
{
    protected const array BUNDLE_CONFIGURATIONS = [
        TraderStrategyConfiguration::class
    ];
}
