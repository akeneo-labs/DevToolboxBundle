<?php

namespace Pim\Bundle\DevToolboxBundle\DependencyInjection;

use PimEnterprise\Bundle\CatalogBundle\Version;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @author Romain Monceau <romain@akeneo.com>
 */
class PimDevToolboxExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services-ee.yml');
    }
}
