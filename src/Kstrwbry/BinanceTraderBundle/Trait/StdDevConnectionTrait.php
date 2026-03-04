<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\StdDevInterface;
use Doctrine\ORM\Mapping as ORM;

trait StdDevConnectionTrait #implements StdDevConnectionInterface
{
    #[ORM\OneToOne(targetEntity: StdDevInterface::class, cascade:['persist'])]
    protected readonly StdDevInterface $stdDev;

    public function getStdDev(): StdDevInterface
    {
        return $this->stdDev;
    }
}
