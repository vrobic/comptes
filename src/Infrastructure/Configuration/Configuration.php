<?php

declare(strict_types=1);

namespace App\Infrastructure\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Définit et valide la configuration du bundle,
 * issue des fichiers situés dans app/config.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @todo : utiliser ->info() sur tous les noeuds.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('comptes');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('import')
                    ->children()
                        ->arrayNode('handlers')
                            ->children()
                                ->arrayNode('mouvements')
                                    ->prototype('array') // @todo : ne pas permettre de clés dynamiques
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('description')->end()
                                            ->scalarNode('extension')->end()
                                            ->node('config', 'variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
