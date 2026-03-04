<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use Doctrine\ORM\Mapping as ORM;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\Column(name:'id', type:'bigint', nullable:false, options:['unsigned' => true], columnDefinition:'BIGSERIAL')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
