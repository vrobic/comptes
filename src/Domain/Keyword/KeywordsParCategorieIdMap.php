<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Categorie\CategorieId;
use App\Domain\DataStructure\Map;

final class KeywordsParCategorieIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            CategorieId::class,
            KeywordCollection::class
        );
    }

    /** @param CategorieId $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key;
    }

    /** @return array<string, Keyword[]> */
    public function toAssociativeArray(): array
    {
        return $this->toArray(
            static fn (CategorieId $categorieId): string => (string) $categorieId,
            static fn (KeywordCollection $keywords): array => $keywords->toArray(
                static fn (Keyword $keyword): Keyword => $keyword
            )
        );
    }
}
