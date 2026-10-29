<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Categorie\Categorie;
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
     * Donne les catégories de ces mots-clés, chacune une seule fois.
     */
    public function catégories(): CategorieCollection
    {
        return new CategorieCollection()->add(
            ...$this->toArray(static fn (Keyword $keyword): Categorie => $keyword->categorie)
        );
    }

    /** @param Keyword $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }
}
