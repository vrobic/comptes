<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\DataStructure\Set;
use App\Domain\Temps\Periode;

final class MouvementCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Mouvement::class);
    }

    /** @param Mouvement $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }

    /**
     * Calcule la balance (débit/crédit) de la liste de mouvements.
     */
    public function balance(?Periode $période): float
    {
        if ($période instanceof Periode) {
            return $this
                ->filter(
                    static fn (Mouvement $mouvement): bool => $mouvement->date >= $période->début && $mouvement->date <= $période->fin
                )
                ->balance(null);
        }

        return $this->reduce(
            static fn (float $balance, Mouvement $mouvement): float => $balance + $mouvement->montant,
            0.
        );
    }
}
