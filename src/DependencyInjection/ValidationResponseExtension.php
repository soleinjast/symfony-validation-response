<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener;

/**
 * Loads and manages the ValidationResponse bundle configuration.
 */
final class ValidationResponseExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
        $listenerDefinition = $container->getDefinition(ValidationExceptionListener::class);
        $listenerDefinition->setArgument('$statusCode', $config['status_code']);
    }
}