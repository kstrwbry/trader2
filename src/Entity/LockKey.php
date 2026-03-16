<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lock_keys')]
class LockKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    protected string $keyId;

    #[ORM\Column(type: 'string', length: 44)]
    protected string $keyToken;

    #[ORM\Column(type: 'integer')]
    protected int $keyExpiration;
}
