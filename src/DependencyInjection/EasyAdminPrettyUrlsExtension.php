<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @codeCoverageIgnore
 */
class EasyAdminPrettyUrlsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            Configuration::ROOT_NODE.'.'.Configuration::ROUTE_PREFIX_NODE,
            $config[Configuration::ROUTE_PREFIX_NODE],
        );

        $container->setParameter(
            Configuration::ROOT_NODE.'.'.Configuration::DEFAULT_DASHBOARD_NODE,
            $config[Configuration::DEFAULT_DASHBOARD_NODE],
        );

        $container->setParameter(
            Configuration::ROOT_NODE.'.'.Configuration::INCLUDE_MENU_INDEX_NODE,
            $config[Configuration::INCLUDE_MENU_INDEX_NODE],
        );

        $container->setParameter(
            Configuration::ROOT_NODE.'.'.Configuration::DROP_ENTITY_FQCN_NODE,
            $config[Configuration::DROP_ENTITY_FQCN_NODE],
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
