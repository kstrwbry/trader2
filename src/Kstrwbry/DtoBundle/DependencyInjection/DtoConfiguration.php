<?php
declare(strict_types=1);

namespace App\Kstrwbry\DtoBundle\DependencyInjection;

use App\Kstrwbry\BaseBundle\DependencyInjection\KstrwbryBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class DtoConfiguration extends KstrwbryBaseConfiguration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = parent::getConfigTreeBuilder();

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('dto_definitions')
                    ->useAttributeAsKey('name') // DTO name as key
                    ->arrayPrototype() // each DTO is an array of dynamic keys
                        ->normalizeKeys(false) // keep keys as-is
                        ->variablePrototype() // allow any scalar type
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
