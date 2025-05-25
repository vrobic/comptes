<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\DataStructure\Set;

final class MouvementCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Mouvement::class);
    }

    /**
     * Calcule la balance (débit/crédit) de la liste de mouvements.
     */
    public function balance(): float
    {
        return $this->reduce(
            static fn (float $balance, Mouvement $mouvement): float => $balance + $mouvement->getMontant(),
            0.
        );
    }
}
