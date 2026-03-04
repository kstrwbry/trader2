<?php
declare(strict_types=1);

namespace App\Kstrwbry\DtoBundle;

use App\Kstrwbry\BaseBundle\KstrwbryBaseBundle;
use App\Kstrwbry\DtoBundle\DependencyInjection\DtoConfiguration;

class KstrwbryDtoBundle extends KstrwbryBaseBundle
{
    public const array BUNDLE_CONFIGURATIONS = [
        DtoConfiguration::class,
    ];
}