<?php

namespace MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'easy_admin_pretty_urls';
    public const ROUTE_PREFIX_NODE = 'route_prefix';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode(self::ROUTE_PREFIX_NODE)
                    ->defaultValue('pretty')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
