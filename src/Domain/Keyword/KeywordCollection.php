<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\DataStructure\Set;

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

            $keywords = $map->has((string) $categorieId) ?
                $map->get((string) $categorieId) :
                new KeywordCollection();

            $keywords = $keywords->add($keyword);

            $map = $map->add((string) $categorieId, $keywords);
        }

        return $map;
    }

    /** @param Keyword $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }
}
