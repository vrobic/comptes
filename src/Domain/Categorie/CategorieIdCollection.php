<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Set;

final class CategorieIdCollection extends Set
{
    public function __construct()
    {
        parent::__construct(CategorieId::class);
    }

    /** @param CategorieId $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value;
    }
}
