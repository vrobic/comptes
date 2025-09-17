<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\Classification;
use App\Domain\DataStructure\Map;

final class MouvementsParClassification extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string',
            MouvementCollection::class
        );
    }

    /** @param string $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key;
    }

    public function ajouter(Classification $classification, Mouvement $mouvement): self
    {
        $map = clone $this;

        if ($map->has($classification->name)) {
            $mouvements = $map->get($classification->name);
            $map = $map->remove($classification->name);
        } else {
            $mouvements = new MouvementCollection();
        }

        return $map->add(
            $classification->name,
            $mouvements->add($mouvement)
        );
    }

    public function getMouvements(): MouvementCollection
    {
        return $this->reduce(
            static fn (MouvementCollection $carry, string $classification, MouvementCollection $mouvements): MouvementCollection => $carry->add(...$mouvements),
            new MouvementCollection()
        );
    }
}
