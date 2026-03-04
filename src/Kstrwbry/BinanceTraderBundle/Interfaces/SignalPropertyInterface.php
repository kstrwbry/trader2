<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Interfaces;

interface SignalPropertyInterface
{
    /**
     * @return Signal::BUY|Signal::SELL|Signal::NEUTRAL
     */
    public function getSignal(): int;

    /**
     * @param Signal::BUY|Signal::SELL|Signal::NEUTRAL $signal
     */
    public function setSignal(int $signal): int;

    public function calcSignal(): int;

    public function getCross(): int;
}
