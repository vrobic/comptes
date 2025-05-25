<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;
use App\Domain\DataStructure\Maybe;
use App\Infrastructure\Repository\CategorieRepository;
use App\Infrastructure\Repository\MouvementRepository;

/**
 * Fournisseur de statistiques.
 */
final readonly class StatsProvider
{
    public function __construct(
        private MouvementRepository $mouvementRepository,
        private CategorieRepository $categorieRepository,
    ) {
    }

    /**
     * Calcule le montant total annuel des mouvements,
     * pour toutes les années incluses dans un intervalle.
     *
     * @param int                   $yearStart année de début, incluse
     * @param int                   $yearEnd   année de fin, incluse
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     *
     * @return array<int, float> les montants des mouvements, classés par années
     */
    public function getYearlyMontants(
        int $yearStart,
        int $yearEnd,
        Maybe $categorie,
        Maybe $compte,
    ): array {
        $dateStart = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearStart-01-01 00:00:00");
        $dateEnd = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearEnd-12-31 23:59:59");

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime)) {
            throw new \Exception('Intervalle de dates invalide.');
        }

        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: $categorie->estDéfini ?
                Maybe::from(
                    $categorie->getValeur() instanceof Categorie ?
                        array_merge([$categorie->getValeur()->getId()], $this->categorieRepository->getCategoriesFillesRecursive($categorie->getValeur()->getId())) :
                        null
                ) :
                Maybe::nothing(),
            compteId: $compte->estDéfini ? Maybe::from($compte->getValeur()->getId()) : Maybe::nothing(),
            dateStart: Maybe::from($dateStart->modify('first day of this year')->setTime(0, 0, 0)),
            dateEnd: Maybe::from($dateEnd->modify('last day of this year')->setTime(23, 59, 59)),
            montant: Maybe::nothing(),
        );

        $yearlyMontants = [];

        foreach ($mouvements as $mouvement) {
            $montant = $mouvement->getMontant();
            $date = $mouvement->getDate();
            $year = (int) $date->format('Y');

            if (!isset($yearlyMontants[$year])) {
                $yearlyMontants[$year] = 0.;
            }

            $yearlyMontants[$year] += $montant;
        }

        return $yearlyMontants;
    }

    /**
     * Calcule le montant mensuel total des mouvements d'une catégorie,
     * compris entre deux dates incluses.
     *
     * @param \DateTime             $dateStart date de début, incluse
     * @param \DateTime             $dateEnd   date de fin, incluse
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     *
     * @return array<int, array<int, float>> les montants des mouvements, classés par mois
     */
    public function getMonthlyMontants(
        \DateTime $dateStart,
        \DateTime $dateEnd,
        Maybe $categorie,
        Maybe $compte,
    ): array {
        $categoriesIds = $categorie->estDéfini ?
            Maybe::from(
                $categorie->getValeur() instanceof Categorie ?
                    array_merge([$categorie->getValeur()->getId()], $this->categorieRepository->getCategoriesFillesRecursive($categorie->getValeur()->getId())) :
                    null
            ) :
            Maybe::nothing();

        // Les montants totaux mensuels des mouvements
        $montants = [];

        // Tous les mois entre et sur les deux dates
        $interval = \DateInterval::createFromDateString('1 month');
        $periods = new \DatePeriod($dateStart, $interval, $dateEnd);

        // Chaque mois de la période
        foreach ($periods as $date) {
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');

            $montants[$year][$month] = 0.;
        }

        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: $categoriesIds,
            compteId: $compte->estDéfini ? Maybe::from($compte->getValeur()->getId()) : Maybe::nothing(),
            dateStart: Maybe::from($dateStart->modify('first day of this month')->setTime(0, 0, 0)),
            dateEnd: Maybe::from($dateEnd->modify('last day of this month')->setTime(23, 59, 59)),
            montant: Maybe::nothing(),
        );

        foreach ($mouvements as $mouvement) {
            $date = $mouvement->getDate();
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');

            $montants[$year][$month] += $mouvement->getMontant();
        }

        return $montants;
    }

    /**
     * Calcule le montant mensuel moyen des mouvements d'une catégorie,
     * compris entre deux dates incluses.
     *
     * @param \DateTime             $dateStart date de début, incluse
     * @param \DateTime             $dateEnd   date de fin, incluse
     * @param Maybe<Categorie|null> $categorie
     * @param Maybe<Compte>         $compte
     *
     * @return float Le montant mensuel moyen des mouvements
     */
    public function getAverageMonthlyMontants(
        \DateTime $dateStart,
        \DateTime $dateEnd,
        Maybe $categorie,
        Maybe $compte,
    ): float {
        $monthlyMontants = $this->getMonthlyMontants($dateStart, $dateEnd, $categorie, $compte);

        $montantTotal = 0.;
        $monthCount = 0;

        foreach ($monthlyMontants as $months) {
            foreach ($months as $montant) {
                $montantTotal += $montant;
                ++$monthCount;
            }
        }

        return $monthCount > 0 ? $montantTotal / $monthCount : 0.;
    }
}
