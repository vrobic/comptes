<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Set;

final class CategorieCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Categorie::class);
    }

    /** @param Categorie $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }

    /** @return array<string, Categorie> */
    public function toAssociativeArray(): array
    {
        $array = [];

        foreach ($this as $categorie) {
            $array[(string) $categorie->id] = $categorie;
        }

        return $array;
    }
}
