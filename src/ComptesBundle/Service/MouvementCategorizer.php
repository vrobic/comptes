<?php

namespace ComptesBundle\Service;

use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Mouvement;
use ComptesBundle\Entity\Repository\KeywordRepository;
use Doctrine\ORM\EntityManager;

/**
 * Service permettant de catégoriser automatiquement
 * les mouvements lorsqu'ils sont importés.
 */
class MouvementCategorizer
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructeur.
     */
    public function __construct(EntityManager $em)
    {
        // Injection de dépendances
        $this->em = $em;
    }

    /**
     * Trouve les catégories probables d'un mouvement.
     *
     * @return Categorie[] Liste de catégories.
     */
    public function getCategories(Mouvement $mouvement): array
    {
        /** @var KeywordRepository $keywordRepository */
        $keywordRepository = $this->em->getRepository('ComptesBundle:Keyword');

        // Tous les mots-clés de description
        $keywords = $keywordRepository->findAll();

        // La description du mouvement
        $description = $mouvement->getDescription();

        // Les catégories probables du mouvement
        $categories = [];

        foreach ($keywords as $keyword) {
            $word = $keyword->getWord();

            // Si le mot-clé est présent dans la description
            if (preg_match("/\b$word\b/i", $description)) {
                $categorie = $keyword->getCategorie();
                $categorieID = $categorie->getId();

                $categories[$categorieID] = $categorie; // Assure l'unicité
            }
        }

        // Reset des clés
        $categories = array_values($categories);

        return $categories;
    }
}
