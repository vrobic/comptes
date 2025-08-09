<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\DataStructure\Maybe;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Periode;

/**
 * Fournisseur de statistiques.
 */
final readonly class StatsProvider
{
    public function __construct(
        private MouvementRepositoryInterface $mouvementRepository,
        private CategorieRepositoryInterface $categorieRepository,
    ) {
    }

    /**
     * Calcule la balance annuelle des mouvements,
     * pour toutes les années incluses dans un intervalle.
     *
     * @param int                   $yearStart Année de début, incluse
     * @param int                   $yearEnd   Année de fin, incluse
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     *
     * @return array<int, float> la balance des mouvements pour chaque année
     */
    public function balanceAnnuelle(
        int $yearStart,
        int $yearEnd,
        Maybe $categorie,
        Maybe $compte,
    ): array {
        $dateStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$yearStart-01-01 00:00:00");
        $dateEnd = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$yearEnd-12-31 23:59:59");

        if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable)) {
            throw new \Exception('Intervalle de dates invalide.');
        }

        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: $categorie->estDéfini ?
                Maybe::from(
                    $categorie->getValeur() instanceof Categorie ?
                        $this->categorieRepository
                            ->getCategoriesFillesRecursive($categorie->getValeur()->id)
                            ->add($categorie->getValeur()->id) :
                        null
                ) :
                Maybe::nothing(),
            compteId: $compte->estDéfini ? Maybe::from($compte->getValeur()->id) : Maybe::nothing(),
            dateStart: Maybe::from($dateStart->modify('first day of this year')->setTime(0, 0, 0)),
            dateEnd: Maybe::from($dateEnd->modify('last day of this year')->setTime(23, 59, 59)),
            montant: Maybe::nothing(),
        );

        $balanceAnnuelle = [];

        /** @var Mouvement $mouvement */
        foreach ($mouvements as $mouvement) {
            $montant = $mouvement->montant;
            $année = (int) $mouvement->date->format('Y');

            if (!isset($balanceAnnuelle[$année])) {
                $balanceAnnuelle[$année] = 0.;
            }

            $balanceAnnuelle[$année] += $montant;
        }

        return $balanceAnnuelle;
    }

    /**
     * Calcule la balance des mouvements d'une catégorie,
     * compris dans la période.
     *
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     *
     * @return array<int, array<int, float>> la balance des mouvements pour chaque mois de chaque année
     */
    public function balanceMensuelle(
        Periode $période,
        Maybe $categorie,
        Maybe $compte,
    ): array {
        $categoriesIds = $categorie->estDéfini ?
            Maybe::from(
                $categorie->getValeur() instanceof Categorie ?
                    $this->categorieRepository
                        ->getCategoriesFillesRecursive($categorie->getValeur()->id)
                        ->add($categorie->getValeur()->id) :
                    null
            ) :
            Maybe::nothing();

        $balanceMensuelle = [];

        // Tous les mois entre et sur les deux dates
        $interval = \DateInterval::createFromDateString('1 month');
        $periods = new \DatePeriod($période->début, $interval, $période->fin);

        // Chaque mois de la période
        foreach ($periods as $date) {
            $année = (int) $date->format('Y');
            $mois = (int) $date->format('m');

            $balanceMensuelle[$année][$mois] = 0.;
        }

        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: $categoriesIds,
            compteId: $compte->estDéfini ? Maybe::from($compte->getValeur()->id) : Maybe::nothing(),
            dateStart: Maybe::from($période->début->modify('first day of this month')->setTime(0, 0, 0)),
            dateEnd: Maybe::from($période->fin->modify('last day of this month')->setTime(23, 59, 59)),
            montant: Maybe::nothing(),
        );

        /** @var Mouvement $mouvement */
        foreach ($mouvements as $mouvement) {
            $date = $mouvement->date;
            $année = (int) $date->format('Y');
            $mois = (int) $date->format('m');

            $balanceMensuelle[$année][$mois] += $mouvement->montant;
        }

        return $balanceMensuelle;
    }

    /**
     * Calcule la balance mensuelle moyenne des mouvements d'une catégorie,
     * compris dans la période.
     *
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     */
    public function balanceMensuelleMoyenne(
        Periode $période,
        Maybe $categorie,
        Maybe $compte,
    ): float {
        $balanceTotale = 0.;
        $nombreDeMois = 0;

        foreach ($this->balanceMensuelle($période, $categorie, $compte) as $months) {
            foreach ($months as $balanceMensuelle) {
                $balanceTotale += $balanceMensuelle;
                ++$nombreDeMois;
            }
        }

        return $nombreDeMois > 0 ? $balanceTotale / $nombreDeMois : 0.;
    }
}
