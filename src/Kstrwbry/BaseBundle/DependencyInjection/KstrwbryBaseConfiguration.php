<?php
declare(strict_types=1);

namespace App\Kstrwbry\BaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

abstract class KstrwbryBaseConfiguration implements ConfigurationInterface
{
    protected string $alias;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        return new TreeBuilder($this->alias);
    }
}