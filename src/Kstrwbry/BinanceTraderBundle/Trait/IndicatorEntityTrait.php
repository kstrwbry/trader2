<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Trait;

use App\Kstrwbry\BinanceTraderBundle\Interfaces\IndicatorEntityInterface;
use Doctrine\ORM\Mapping as ORM;

trait IndicatorEntityTrait
{
    use
        IdTrait,
        KlineConnectionTrait
    ;

    protected IndicatorEntityInterface|null $prevEntity = null;
    #[ORM\Column(name:'prev_entity_id', type:'bigint', nullable:true, options:['unsigned' => true])]
    protected readonly int|null $prevEntityId;

    protected IndicatorEntityInterface|null $outdatedEntity = null;
    #[ORM\Column(name:'outdated_entity_id', type:'bigint', nullable:true, options:['unsigned' => true])]
    protected readonly int|null $outdatedEntityId;

    public function getPrevEntityId(): int|null
    {
        return $this->prevEntityId;
    }

    public function getPrevEntity(): IndicatorEntityInterface|null
    {
        return $this->prevEntity;
    }

    public function setPrevEntity(IndicatorEntityInterface|null $prevEntity): static
    {
        $this->prevEntity = $prevEntity;

        return $this;
    }

    public function getOutdatedEntityId(): int|null
    {
        return $this->outdatedEntityId;
    }

    public function getOutdatedEntity(): IndicatorEntityInterface|null
    {
        return $this->outdatedEntity;
    }

    public function setOutdatedEntity(IndicatorEntityInterface|null $outdatedEntity): static
    {
        $this->outdatedEntity = $outdatedEntity;

        return $this;
    }

    public function __destruct()
    {
        $this->setPrevEntity(null);
        $this->setOutdatedEntity(null);
    }
}
