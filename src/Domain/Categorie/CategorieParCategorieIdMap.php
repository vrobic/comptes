<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Map;

final class CategorieParCategorieIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string',
            Categorie::class
        );
    }

    /** @param string $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key;
    }

    /** @return array<string, Categorie> */
    public function toAssociativeArray(): array
    {
        return $this->toArray(
            static fn (string $categorieId): string => $categorieId,
            static fn (Categorie $categorie): Categorie => $categorie
        );
    }
}
