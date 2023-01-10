<?php

namespace MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'easy_admin_pretty_urls';
    public const ROUTE_PREFIX_NODE = 'route_prefix';
    public const DEFAULT_DASHBOARD_NODE = 'default_dashboard';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode(self::ROUTE_PREFIX_NODE)
                    ->defaultValue('pretty')
                ->end()
                ->scalarNode(self::DEFAULT_DASHBOARD_NODE)
                    ->defaultValue('App\\Controller\\EasyAdmin\\DashboardController::index')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
