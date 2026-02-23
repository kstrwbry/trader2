<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface TraderConsts
{
    public const int MACD_EVEN            = 0;
    public const int MACD_OVER_SIGNAL_LINE = 1;
    public const int MACD_UNDER_SIGNAL_LINE  = -1;

    public const int SIGNAL_NEUTRAL = 0;
    public const int SIGNAL_BUY     = 1;
    public const int SIGNAL_SELL    = -1;
}
