<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Map;

final class CategorieParId extends Map
{
    public function __construct()
    {
        parent::__construct('int', Categorie::class);
    }
}
