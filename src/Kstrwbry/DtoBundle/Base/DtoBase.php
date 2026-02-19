<?php
declare(strict_types=1);

namespace App\Kstrwbry\DtoBundle\Base;

use App\Kstrwbry\DtoBundle\Interfaces\DTOInterface;

class DtoBase implements DTOInterface
{
    public function serialize(): string
    {
        return json_encode($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(json_decode($data, true));
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    public function __unserialize(array $data): void
    {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }
    }
}
