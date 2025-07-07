<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Map;

final class CategorieParCategorieIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string', // Le map ne supporte pas bien d'avoir des objets comme clés
            Categorie::class
        );
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
