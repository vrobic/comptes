<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Categorie\CategorieCollection;
use App\Domain\DataStructure\Set;

/**
 * @extends Set<Keyword>
 */
final class KeywordCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Keyword::class);
    }

    public function trierParCatégorie(): KeywordsParCategorieIdMap
    {
        $map = new KeywordsParCategorieIdMap();

        /** @var Keyword $keyword */
        foreach ($this as $keyword) {
            $categorieId = $keyword->categorie->id;

            if ($map->has($categorieId)) {
                $keywords = $map->get($categorieId);
                $map = $map->remove($categorieId);
            } else {
                $keywords = new KeywordCollection();
            }

            $keywords = $keywords->add($keyword);

            $map = $map->add($categorieId, $keywords);
        }

        return $map;
    }

    /**
     * Trouve les catégories probables d'un mouvement, d'après sa description.
     */
    public function trouverLesCatégories(string $description): CategorieCollection
    {
        // Les catégories probables du mouvement
        $categories = new CategorieCollection();

        /** @var Keyword $keyword */
        foreach ($this as $keyword) {
            // Si le mot-clé est présent dans la description
            if ($keyword->estPrésentDans($description)) {
                $categories = $categories->add($keyword->categorie);
            }
        }

        return $categories;
    }

    /** @param Keyword $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }
}
