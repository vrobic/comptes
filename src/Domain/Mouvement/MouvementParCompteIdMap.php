<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Compte\CompteId;
use App\Domain\DataStructure\Map;

final class MouvementParCompteIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            CompteId::class,
            Mouvement::class
        );
    }

    /** @param CompteId $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key;
    }
}
