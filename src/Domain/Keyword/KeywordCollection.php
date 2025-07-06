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

    public function trierParCatégorie(): KeywordsParCategorieMap
    {
        $keywordsParCategorie = new KeywordsParCategorieMap();

        /** @var Keyword $keyword */
        foreach ($this as $keyword) {
            $categorieId = $keyword->categorie->id;

            $keywords = $keywordsParCategorie->has((string) $categorieId) ?
                $keywordsParCategorie->get((string) $categorieId) :
                new KeywordCollection();

            $keywords = $keywords->add($keyword);

            $keywordsParCategorie = $keywordsParCategorie->add((string) $categorieId, $keywords);
        }

        return $keywordsParCategorie;
    }

    /** @param Keyword $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }
}
