<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }
}
