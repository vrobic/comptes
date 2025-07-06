<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Map;

final class CategorieParIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string', // Le map ne supporte pas bien d'avoir des objets comme clés
            Categorie::class
        );
    }
}
