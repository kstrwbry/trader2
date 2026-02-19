<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface TraderConsts
{
    public const int MACD_EVEN            = 0;
    public const int MACD_SHORT_IS_HIGHER = 1;
    public const int MACD_LONG_IS_HIGHER  = -1;

    public const int SIGNAL_NEUTRAL = 0;
    public const int SIGNAL_BUY     = 1;
    public const int SIGNAL_SELL    = -1;
}
