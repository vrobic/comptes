<?php

namespace Comptes\Bundle\CoreBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;

/**
 * Service permettant de catégoriser automatiquement
 * les mouvements lorsqu'ils sont importés.
 */
class MouvementCategorizer
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructeur.
     *
     * @param Container $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em)
    {
        // Injection de dépendances
        $this->container = $container;
        $this->em = $em;
    }

    /**
     * Trouve les catégories probables d'un mouvement.
     *
     * @param Mouvement $mouvement
     * @throws \Exception Si l'une des catégories est inconnue.
     * @return Categorie[] Liste de catégories.
     */
    function getCategories($mouvement)
    {
        $categorieRepository = $this->em->getRepository('ComptesCoreBundle:Categorie');

        // Chargement de la configuration
        $configurationLoader = $this->container->get('comptes_core.configuration.loader');
        $configuration = $configurationLoader->load('import.yml');

        // Tableau de correspondance entre les mots-clés de description et leurs catégories
        $keywords = isset($configuration['keywords']) ? $configuration['keywords'] : array();

        // La description du mouvement
        $description = $mouvement->getDescription();

        // Les catégories probables du mouvement
        $categories = array();

        foreach ($keywords as $keyword => $categorieID)
        {
            // Si le mot-clé est présent dans la description
            if (preg_match("/\b$keyword\b/", $description))
            {
                $categorie = $categorieRepository->find($categorieID);

                if (!$categorie)
                {
                    throw new \Exception("La catégorie n°$categorieID est inconnue.");
                }

                $categories[$categorieID] = $categorie; // Assure l'unicité
            }
        }

        // Reset des clés
        $categories = array_values($categories);

        return $categories;
    }
}