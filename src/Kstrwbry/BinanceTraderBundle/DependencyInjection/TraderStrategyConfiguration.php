<?php
declare(strict_types=1);

namespace App\Kstrwbry\BinanceTraderBundle\DependencyInjection;

use App\Kstrwbry\BaseBundle\DependencyInjection\KstrwbryBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class TraderStrategyConfiguration extends KstrwbryBaseConfiguration
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = parent::getConfigTreeBuilder();

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('trader_strategy')
                    ->useAttributeAsKey('strategy_name') // e.g. macd-rvi
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('stop_loss_condition')->isRequired()->end()
                            ->arrayNode('indicators')
                                ->isRequired()
                                ->children()
                                    ->arrayNode('macd')
                                        ->isRequired()
                                        ->children()
                                            ->integerNode('short_period')->isRequired()->end()
                                            ->integerNode('long_period')->isRequired()->end()
                                            ->integerNode('signal_period')->isRequired()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('rvi')
                                        ->isRequired()
                                        ->children()
                                            ->integerNode('period')->isRequired()->end()
                                            ->integerNode('lower_signal_line')->isRequired()->end()
                                            ->integerNode('upper_signal_line')->isRequired()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end() // indicators
                        ->end()
                    ->end() // strategy array
                ->end() // trader_strategy
            ->end() // children of root
        ;

        return $treeBuilder;
    }
}
