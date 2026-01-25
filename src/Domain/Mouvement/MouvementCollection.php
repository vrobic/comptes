<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\BalanceAnnuelle;
use App\Domain\BalanceMensuelle;
use App\Domain\Categorie\CategorieId;
use App\Domain\DataStructure\Set;
use App\Domain\Temps\Annee;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;

/**
 * @extends Set<Mouvement>
 */
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

    public function trierParDate(): self
    {
        return $this->sort(
            static fn (Mouvement $mouvement1, Mouvement $mouvement2): int => $mouvement1->date <=> $mouvement2->date
        );
    }

    public function filtrerParPériode(Periode $période): self
    {
        return $this->filter(
            static fn (Mouvement $mouvement): bool => $mouvement->date >= $période->début && $mouvement->date <= $période->fin
        );
    }

    public function filtrerParCatégorie(CategorieId $catégorieId): self
    {
        return $this->filter(
            static fn (Mouvement $mouvement): bool => true === $mouvement->categorie?->id->estÉgalÀ($catégorieId)
        );
    }

    public function getPériode(): Periode
    {
        if ($this->isEmpty()) {
            throw new \LogicException('Impossible de calculer une période sur une collection vide.');
        }

        $set = $this->trierParDate();

        /** @var Mouvement $premierMouvement */
        $premierMouvement = $set->first();

        /** @var Mouvement $dernierMouvement */
        $dernierMouvement = $set->last();

        return new Periode($premierMouvement->date, $dernierMouvement->date);
    }

    public function balance(): Balance
    {
        return $this->reduce(
            static fn (Balance $balance, Mouvement $mouvement): Balance => $balance->additionner(
                new Balance($mouvement->montant->montant)
            ),
            Balance::nulle()
        );
    }

    public function balanceAnnuelle(Periode $période): BalanceAnnuelle
    {
        $balanceAnnuelle = new BalanceAnnuelle();

        if ($this->isEmpty()) {
            return $balanceAnnuelle;
        }

        /**
         * Initialise toutes les années de la période à zéro.
         *
         * @var Annee $année
         */
        foreach ($this->getPériode()->étendre($période)->années() as $année) {
            $balanceAnnuelle = $balanceAnnuelle->ajouter($année, Balance::nulle());
        }

        /** @var Mouvement $mouvement */
        foreach ($this as $mouvement) {
            $année = Annee::fromDate($mouvement->date);

            $balanceAnnuelle = $balanceAnnuelle->ajouter($année, new Balance($mouvement->montant->montant));
        }

        return $balanceAnnuelle;
    }

    public function balanceMensuelle(Periode $période): BalanceMensuelle
    {
        $balanceMensuelle = new BalanceMensuelle();

        if ($this->isEmpty()) {
            return $balanceMensuelle;
        }

        /**
         * Initialise tous les mois de la période à zéro.
         *
         * @var Mois $mois
         */
        foreach ($this->getPériode()->étendre($période)->mois() as $mois) {
            $balanceMensuelle = $balanceMensuelle->ajouter($mois, Balance::nulle());
        }

        /** @var Mouvement $mouvement */
        foreach ($this as $mouvement) {
            $mois = Mois::fromDate($mouvement->date);

            $balanceMensuelle = $balanceMensuelle->ajouter($mois, new Balance($mouvement->montant->montant));
        }

        return $balanceMensuelle;
    }
}
