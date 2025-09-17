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
            Classification::class,
            MouvementCollection::class
        );
    }

    /** @param Classification $key */
    public function getUniqueKey(mixed $key): string
    {
        return $key->name;
    }

    public function ajouter(Classification $classification, Mouvement $mouvement): self
    {
        $map = clone $this;

        if ($map->has($classification)) {
            $mouvements = $map->get($classification);
            $map = $map->remove($classification);
        } else {
            $mouvements = new MouvementCollection();
        }

        return $map->add(
            $classification,
            $mouvements->add($mouvement)
        );
    }

    public function getMouvements(): MouvementCollection
    {
        return $this->reduce(
            static fn (MouvementCollection $carry, Classification $classification, MouvementCollection $mouvements): MouvementCollection => $carry->add(...$mouvements),
            new MouvementCollection()
        );
    }
}
