<?php

namespace Commander\JsonValidationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('commander_json_validation');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->booleanNode('enable_request_listener')->defaultTrue()->end()
                ->booleanNode('enable_response_listener')->defaultTrue()->end()
                ->booleanNode('enable_exception_listener')->defaultTrue()->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
