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
}
