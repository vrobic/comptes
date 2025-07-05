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

    public function trierParCatégorie(): KeywordsParCategorie
    {
        $keywordsParCategorie = new KeywordsParCategorie();

        foreach ($this as $keyword) {
            $categorieID = $keyword->getCategorie()->getId();

            $keywords = $keywordsParCategorie->has($categorieID) ?
                $keywordsParCategorie->get($categorieID) :
                new KeywordCollection();

            $keywords = $keywords->add($keyword);

            $keywordsParCategorie = $keywordsParCategorie->add($categorieID, $keywords);
        }

        return $keywordsParCategorie;
    }
}
