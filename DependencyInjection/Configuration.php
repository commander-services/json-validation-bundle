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
            ->children()
                ->arrayNode('resolver')
                    ->children()
                        ->arrayNode('register_file')
                            ->info('Register a file as a schema. You must specify the id and file path.')
                            ->example('\'http://example.com/schema.json\': \'%kernel.project_dir%/path/to/schema/file.json\'')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('register_prefix')
                            ->info('Register a filesystem directory from where to load schema files. You must specify the id prefix and directory path.')
                            ->example('\'http://example.com/\': \'%kernel.project_dir%/path/to/schema/dir\'')
                            ->useAttributeAsKey('prefix')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
