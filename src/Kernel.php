<?php
declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function getKernelParameters(): array
    {
        return array_merge(
            parent::getKernelParameters(),
            [
                'kernel.app_dir' => $this->getProjectDir() . DIRECTORY_SEPARATOR . 'src',
            ],
        );
    }
}
