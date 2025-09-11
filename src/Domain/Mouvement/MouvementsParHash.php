<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\DataStructure\Map;

final class MouvementsParHash extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string', // Le map ne supporte pas bien d'avoir des objets comme clés
            Mouvement::class
        );
    }

    /** @param string $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key;
    }
}
