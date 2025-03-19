<?php

namespace Commander\JsonValidationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('commander_json_validation');
        $rootNode    = method_exists(TreeBuilder::class, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('commander_json_validation');
        $rootNode
            ->children()
            ->booleanNode('enable_request_listener')->defaultTrue()->end()
            ->booleanNode('enable_response_listener')->defaultTrue()->end()
            ->booleanNode('enable_exception_listener')->defaultTrue()->end();

        return $treeBuilder;
    }
}
