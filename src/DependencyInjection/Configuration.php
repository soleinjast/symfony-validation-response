<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for the ValidationResponse bundle.
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('validation_response');
        $rootNode = $treeBuilder->getRootNode();

        // @formatter:off
        $rootNode
            ->children()
                ->integerNode('status_code')
                    ->defaultValue(422)
                    ->min(400)
                    ->max(599)
                    ->info('HTTP status code for validation errors')
                ->end()
                ->scalarNode('format')
                    ->defaultValue('simple')
                    ->info('Default error format: simple, rfc7807 or simple-nested')
                    ->validate()
                    ->ifNotInArray(['simple', 'rfc7807', 'simple-nested'])
                        ->thenInvalid('Invalid format "%s". Must be either "simple", "rfc7807" or "simple-nested".')
                    ->end()
                ->end()
                ->arrayNode('rfc7807')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->defaultValue('about:blank')
                            ->info('URI reference that identifies the problem type')
                        ->end()
                        ->scalarNode('title')
                            ->defaultValue('Validation Failed')
                            ->info('Short, human-readable summary of the problem')
                        ->end()
                    ->end()
            ->end()
        ->end();
        // @formatter:on

        return $treeBuilder;
    }
}
