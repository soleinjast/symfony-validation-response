<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\DependencyInjection;

use Soleinjast\ValidationResponse\Command\TestValidationCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener;
use Soleinjast\ValidationResponse\Formatter\SimpleFormatter;
use Soleinjast\ValidationResponse\Formatter\RFC7807Formatter;
use Soleinjast\ValidationResponse\Formatter\NestedFormatter;

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
        $container->register(NestedFormatter::class)
            ->setPublic(false);
        $container->register(RFC7807Formatter::class)
            ->setPublic(false)
            ->setArguments([
                '$type' => $config['rfc7807']['type'],
                '$title' => $config['rfc7807']['title'],
            ]);
        $formatterClass = match($config['format']) {
            'rfc7807' => RFC7807Formatter::class,
            'nested' => NestedFormatter::class,
            default => SimpleFormatter::class,
        };
        // Configure the event listener
        $listenerDefinition = $container->getDefinition(ValidationExceptionListener::class);
        $listenerDefinition->setArgument('$formatter', new Reference($formatterClass));
        $listenerDefinition->setArgument('$statusCode', $config['status_code']);
        // Configure the command with the same formatter
        $commandDefinition = $container->getDefinition(TestValidationCommand::class);
        $commandDefinition->setArgument('$formatter', new Reference($formatterClass));
    }
}
