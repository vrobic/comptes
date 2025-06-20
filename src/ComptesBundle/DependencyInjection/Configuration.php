<?php

namespace ComptesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
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
     * {@inheritdoc}
     *
     * @todo : utiliser ->info() sur tous les noeuds.
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        /** @var ParentNodeDefinitionInterface $rootNode */
        $rootNode = $treeBuilder->root('comptes');

        $rootNode
            ->children()
                ->arrayNode('fixtures')
                    ->children()
                        ->arrayNode('carburants')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('nom')
                                        ->info("Nom commercial du carburant.")
                                    ->end()
                                    ->integerNode('rang')
                                        ->info("Ordre d'affichage du carburant dans les listes.")
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('vehicules')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('nom')
                                        ->info("Marque et modèle du véhicule.")
                                    ->end()
                                    ->scalarNode('date_achat')
                                        ->info("Date d'achat du véhicule, au format Y-m-d.")
                                    ->end()
                                    ->scalarNode('date_vente')
                                        ->info("Date de revente du véhicule, au format Y-m-d. Laisser vide si vous en êtes encore en possession.")
                                        ->defaultValue(null)
                                    ->end()
                                    ->floatNode('kilometrage_achat')
                                        ->info("Kilométrage du véhicule à son achat. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->floatNode('kilometrage_initial')
                                        ->info("Kilométrage du véhicule après le premier plein rentré dans l'application. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->floatNode('prix_achat')
                                        ->info("Prix d'achat du véhicule, en euros. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->integerNode('carburant')
                                        ->info("Identifiant du carburant.")
                                        ->min(1)
                                    ->end()
                                    ->floatNode('capacite_reservoir')
                                        ->info("Capacité du réservoir, en litres. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->integerNode('rang')
                                        ->info("Ordre d'affichage du véhicule dans les listes.")
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('comptes')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('nom')
                                        ->info("Nom du compte.")
                                    ->end()
                                    ->scalarNode('numero')
                                        ->info("Numéro du compte.")
                                    ->end()
                                    ->scalarNode('banque')
                                        ->info("Domiciliation du compte.")
                                    ->end()
                                    ->floatNode('plafond')
                                        ->info("Plafond du compte, en euros. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->floatNode('solde_initial')
                                        ->info("Solde initial du compte en euros, avant le premier mouvement rentré dans l'application. Le séparateur de décimales est le point.")
                                    ->end()
                                    ->scalarNode('date_ouverture')
                                        ->info("Date d'ouverture du compte, au format Y-m-d.")
                                    ->end()
                                    ->scalarNode('date_fermeture')
                                        ->info("Date de fermeture éventuelle du compte, au format Y-m-d. Laisser vide si le compte est toujours ouvert.")
                                        ->defaultValue(null)
                                    ->end()
                                    ->integerNode('rang')
                                        ->info("Ordre d'affichage du compte dans les listes.")
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('categories')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('nom')
                                        ->info("Nom de la catégorie.")
                                    ->end()
                                    ->integerNode('rang')
                                        ->info("Ordre d'affichage de la catégorie dans les listes.")
                                    ->end()
                                    // @todo : déclarer récursivement la structure des sous-catégories
                                    ->variableNode('subcategories')
                                        ->info("Liste éventuelle de sous-catégories. Laisser vide si la catégorie n'a pas de sous-catégories. Le format attendu est le même que pour les catégories de premier niveau.")
                                        ->defaultValue([])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('import')
                    ->children()
                        ->arrayNode('handlers')
                            ->children()
                                ->arrayNode('mouvements')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('name')->end()
                                            ->scalarNode('description')->end()
                                            ->scalarNode('extension')->end()
                                            ->node('config', 'variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('pleins')
                                    ->prototype('array')
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
