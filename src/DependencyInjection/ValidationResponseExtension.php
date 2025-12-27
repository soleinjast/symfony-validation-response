<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener;
use Soleinjast\ValidationResponse\Formatter\SimpleFormatter;
use Soleinjast\ValidationResponse\Formatter\RFC7807Formatter;

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
        $container->register(SimpleFormatter::class)
            ->setPublic(false);
        $container->register(RFC7807Formatter::class)
            ->setPublic(false)
            ->setArguments([
                '$type' => $config['rfc7807']['type'],
                '$title' => $config['rfc7807']['title'],
            ]);
        $formatterClass = match($config['format']) {
            'rfc7807' => RFC7807Formatter::class,
            default => SimpleFormatter::class,
        };
        $listenerDefinition = $container->getDefinition(ValidationExceptionListener::class);
        $listenerDefinition->setArgument('$formatter', new Reference($formatterClass));
        $listenerDefinition->setArgument('$statusCode', $config['status_code']);
    }
}