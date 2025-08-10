<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\CategorieId;
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

    public function filtrerParPériode(Periode $période): self
    {
        return $this->filter(
            static fn (Mouvement $mouvement): bool => $mouvement->date >= $période->début && $mouvement->date <= $période->fin
        );
    }

    public function filtrerParCatégorieId(CategorieId $catégorieId): self
    {
        return $this->filter(
            static fn (Mouvement $mouvement): bool => true === $mouvement->categorie?->id->estÉgalÀ($catégorieId)
        );
    }

    /**
     * Calcule la balance (débit/crédit) de la liste de mouvements.
     */
    public function balance(): float
    {
        return $this->reduce(
            static fn (float $balance, Mouvement $mouvement): float => $balance + $mouvement->montant,
            0.
        );
    }

    /**
     * @return array<int, float> la balance des mouvements pour chaque année
     */
    public function balanceAnnuelle(Periode $période): array
    {
        $balanceAnnuelle = [];

        if ($this->isEmpty()) {
            return $balanceAnnuelle;
        }

        // Initialise tous les mois de la période à zéro
        foreach ($période->années() as $année) {
            $balanceAnnuelle[$année] = 0.;
        }

        /** @var Mouvement $mouvement */
        foreach ($this as $mouvement) {
            $année = (int) $mouvement->date->format('Y');

            $balanceAnnuelle[$année] = ($balanceAnnuelle[$année] ?? 0.) + $mouvement->montant;
        }

        /* Le tableau était initialement trié mais la boucle ci-dessus peut avoir mis le bazar.
         * Trier ici est plus performant que de boucler sur une collection déjà triée. */
        ksort($balanceAnnuelle);

        return $balanceAnnuelle;
    }

    /**
     * @return array<int, array<int, float>> la balance des mouvements pour chaque mois de chaque année
     */
    public function balanceMensuelle(Periode $période): array
    {
        $balanceMensuelle = [];

        if ($this->isEmpty()) {
            return $balanceMensuelle;
        }

        // Initialise tous les mois de la période à zéro
        foreach ($période->mois() as $année => $m) {
            foreach ($m as $mois) {
                $balanceMensuelle[$année][$mois] = 0.;
            }
        }

        /** @var Mouvement $mouvement */
        foreach ($this as $mouvement) {
            $année = (int) $mouvement->date->format('Y');
            $mois = (int) $mouvement->date->format('m');

            $balanceMensuelle[$année][$mois] = ($balanceMensuelle[$année][$mois] ?? 0.) + $mouvement->montant;
        }

        /* Le tableau était initialement trié mais la boucle ci-dessus peut avoir mis le bazar.
         * Trier ici est plus performant que de boucler sur une collection déjà triée. */
        foreach ($balanceMensuelle as &$mois) {
            ksort($mois);
        }
        ksort($balanceMensuelle);

        return $balanceMensuelle;
    }

    public function balanceMensuelleMoyenne(Periode $période): float
    {
        $balanceTotale = 0.;
        $nombreDeMois = 0;

        foreach ($this->balanceMensuelle($période) as $months) {
            foreach ($months as $balanceMensuelle) {
                $balanceTotale += $balanceMensuelle;
                ++$nombreDeMois;
            }
        }

        return $nombreDeMois > 0 ? $balanceTotale / $nombreDeMois : 0.;
    }
}
