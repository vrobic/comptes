<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\DataStructure\Map;
use App\Domain\Mouvement\Balance;

final class BalanceParCategorie extends Map
{
    public function __construct()
    {
        parent::__construct(CategorieId::class, Balance::class);
    }

    /** @param CategorieId $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key;
    }
}
