<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dto_management');

        $rootNode
            ->children()
                ->arrayNode('namespaces')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function ($values): bool {
                            return 0 === count($values);
                        })
                        ->thenInvalid('You must specify at least one namespace and its base directory')
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('namespace')
                                ->cannotBeEmpty()
                                ->info('Namespace where dtos can be found.')
                            ->end()
                            ->scalarNode('base_dir')
                                ->cannotBeEmpty()
                                ->info('Directory where dtos can be found.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
