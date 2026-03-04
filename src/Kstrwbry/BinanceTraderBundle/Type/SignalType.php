<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\SmallIntType;

class SignalType extends SmallIntType
{
    public CONST string TYPE_NAME = 'signal';

    /** {@inheritdoc} */
    public function getName(): string
    {
        return static::TYPE_NAME;
    }

    /** {@inheritdoc} */
    public function convertToPHPValue($value, AbstractPlatform $platform): int
    {
        return $this->convertValue((int)$value);
    }

    /** {@inheritDoc} */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): int
    {
        return $this->convertValue((int)$value);
    }

    private function convertValue(int $value): int
    {
        return $value <=> 0;
    }
}
