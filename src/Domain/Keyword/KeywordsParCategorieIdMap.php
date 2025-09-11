<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\DataStructure\Map;

final class KeywordsParCategorieIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string',
            KeywordCollection::class
        );
    }

    /** @param string $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key;
    }

    /** @return array<string, Keyword[]> */
    public function toAssociativeArray(): array
    {
        return $this->toArray(
            static fn (string $categorieId): string => $categorieId,
            static fn (KeywordCollection $keywords): array => $keywords->toArray(
                static fn (Keyword $keyword): Keyword => $keyword
            )
        );
    }
}
