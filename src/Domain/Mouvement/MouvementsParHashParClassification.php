<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\DataStructure\Map;

final class MouvementsParHashParClassification extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string',
            MouvementsParHash::class
        );
    }

    /** @param string $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key;
    }

    public function getMouvementsParHash(): MouvementsParHash
    {
        $map = new MouvementsParHash();

        foreach ($this as $mouvementsParHash) {
            foreach ($mouvementsParHash as $hash => $mouvement) {
                $map = $map->add($hash, $mouvement);
            }
        }

        return $map;
    }
}
