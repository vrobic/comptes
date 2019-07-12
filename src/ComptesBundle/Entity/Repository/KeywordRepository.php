<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository des mots-clés.
 */
class KeywordRepository extends EntityRepository
{
    /**
     * Récupère tous les mots-clés, classés par catégories.
     *
     * @todo Utiliser une requête SQL.
     *
     * @return array
     */
    public function findAllSortedByCategories()
    {
        $keywords = array();
        $unsortedKeywords = $this->findAll();

        foreach ($unsortedKeywords as $keyword) {
            $categorie = $keyword->getCategorie();
            $categorieID = $categorie->getId();

            if (!isset($keywords[$categorieID])) {
                $keywords[$categorieID] = array();
            }

            $keywords[$categorieID][] = $keyword;
        }

        return $keywords;
    }
}
