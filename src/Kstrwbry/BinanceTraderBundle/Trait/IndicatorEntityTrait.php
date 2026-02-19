<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

# use Doctrine\ORM\Mapping as ORM;
use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;

trait IndicatorEntityTrait
{
    use KlineConnectionTrait;

    # #[ORM\OneToOne(targetEntity: IndicatorEntityInterface::class, cascade: ['persist'])]
    # protected $prevEntity = null;

    abstract public function getPrevEntity(): IndicatorEntityInterface|null;
}
