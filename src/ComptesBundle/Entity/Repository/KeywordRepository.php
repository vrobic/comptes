<?php

namespace ComptesBundle\Entity\Repository;

use ComptesBundle\Entity\Keyword;
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
     * @return array<int, Keyword[]>
     */
    public function findAllSortedByCategories(): array
    {
        $keywords = [];

        /** @var Keyword[] $unsortedKeywords */
        $unsortedKeywords = $this->findAll();

        foreach ($unsortedKeywords as $keyword) {
            $categorie = $keyword->getCategorie();
            $categorieID = $categorie->getId();

            if (!isset($keywords[$categorieID])) {
                $keywords[$categorieID] = [];
            }

            $keywords[$categorieID][] = $keyword;
        }

        return $keywords;
    }
}
