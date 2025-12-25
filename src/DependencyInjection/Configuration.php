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

        $rootNode
            ->children()
            ->integerNode('status_code')
            ->defaultValue(422)
            ->min(400)
            ->max(599)
            ->info('HTTP status code for validation errors')
            ->end();

        return $treeBuilder;
    }
}