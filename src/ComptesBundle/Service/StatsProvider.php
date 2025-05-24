<?php

namespace ComptesBundle\Service;

use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Repository\CompteRepository;
use ComptesBundle\Entity\Repository\MouvementRepository;
use ComptesBundle\Entity\Repository\PleinRepository;
use ComptesBundle\Entity\Categorie;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Fournisseur de statistiques.
 */
class StatsProvider
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * Configuration des statistiques.
     *
     * @var array
     */
    protected $configuration;

    /**
     * Constructeur.
     */
    public function __construct(RegistryInterface $doctrine, ConfigurationLoader $configurationLoader)
    {
        // Injection de dépendances
        $this->doctrine = $doctrine;

        // Chargement de la configuration
        $configuration = $configurationLoader->load('stats');
        $this->configuration = $configuration;
    }

    /**
     * Calcule la balance mensuelle moyenne (crédit/débit) de tous les mouvements
     * compris entre deux dates, incluses.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     */
    public function getMonthlyBalance(\DateTime $dateStart, \DateTime $dateEnd): float
    {
        /** @var CompteRepository $compteRepository */
        $compteRepository = $this->doctrine->getRepository('ComptesBundle:Compte');

        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $this->doctrine->getRepository('ComptesBundle:Mouvement');

        // Nombre de mois entre les deux dates
        $diff = $dateStart->diff($dateEnd);
        $monthsCount = $diff->y * 12 + $diff->m + $diff->d / 30;

        if ($monthsCount < 1) {
            $monthsCount = 1;
        }

        // Tous les mouvements entre ces deux dates, et leur balance
        $mouvements = $mouvementRepository->findByDate($dateStart, $dateEnd);

        $balance = $compteRepository->getBalanceByMouvements($mouvements);

        // Balance mensuelle moyenne
        $monthlyBalance = $balance / $monthsCount;

        return $monthlyBalance;
    }

    /**
     * Calcule le montant total annuel des mouvements,
     * pour toutes les années incluses dans un intervalle.
     *
     * @param int     $yearStart Année de début, incluse.
     * @param int     $yearEnd   Année de fin, incluse.
     * @param ?Compte $compte    Un compte, facultatif.
     *
     * @return array<int, float> Les montants des mouvements, classés par années.
     */
    public function getYearlyMontants(int $yearStart, int $yearEnd, ?Compte $compte = null): array
    {
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $this->doctrine->getRepository('ComptesBundle:Mouvement');

        $dateStart = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearStart-01-01 00:00:00");
        $dateEnd = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearEnd-12-31 23:59:59");

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime)) {
            throw new \Exception("Intervalle de dates invalide.");
        }

        $mouvements = $mouvementRepository->findByDate($dateStart, $dateEnd, 'ASC', $compte);

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
     * Calcule le montant total annuel des mouvements d'une catégorie,
     * pour toutes les années incluses dans un intervalle.
     *
     * @param ?Categorie $categorie
     * @param int        $yearStart Année de début, incluse.
     * @param int        $yearEnd   Année de fin, incluse.
     * @param ?Compte    $compte    Un compte, facultatif.
     *
     * @return array<int, float> Les montants des mouvements de la catégorie, classés par années.
     */
    public function getYearlyMontantsByCategorie(?Categorie $categorie, int $yearStart, int $yearEnd, ?Compte $compte = null): array
    {
        $dateStart = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearStart-01-01 00:00:00");
        $dateEnd = \DateTime::createFromFormat('Y-m-d H:i:s', "$yearEnd-12-31 23:59:59");

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime)) {
            throw new \Exception("Intervalle de dates invalide.");
        }

        $yearlyMontants = [];
        $monthlyMontants = $this->getMonthlyMontantsByCategorie($categorie, $dateStart, $dateEnd, $compte);

        foreach ($monthlyMontants as $year => $months) {
            $yearlyMontants[$year] = 0.;

            foreach ($months as $monthlyMontant) {
                $yearlyMontants[$year] += $monthlyMontant;
            }
        }

        return $yearlyMontants;
    }

    /**
     * Calcule le montant mensuel total des mouvements d'une catégorie,
     * compris entre deux dates incluses.
     *
     * @param ?Categorie $categorie
     * @param \DateTime  $dateStart Date de début, incluse.
     * @param \DateTime  $dateEnd   Date de fin, incluse.
     * @param ?Compte    $compte    Un compte, facultatif.
     *
     * @return array<int, array<int, float>> Les montants des mouvements de la catégorie, classés par mois.
     */
    public function getMonthlyMontantsByCategorie(?Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd, ?Compte $compte = null): array
    {
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $this->doctrine->getRepository('ComptesBundle:Mouvement');

        // Les montants totaux mensuels des mouvements de la catégorie
        $montants = [];

        // Tous les mois entre et sur les deux dates
        $interval = \DateInterval::createFromDateString('1 month');
        $periods = new \DatePeriod($dateStart, $interval, $dateEnd);

        // Chaque mois de la période
        foreach ($periods as $date) {
            $year = (int) $date->format('Y');
            $month = (int) $date->format('m');
            $day = (int) $date->format('d');

            $monthStartDate = new \DateTime();
            $monthStartDate
                ->setDate($year, $month, $day)
                ->setTime(0, 0)
                ->modify('first day of this month')
            ;

            $monthEndDate = new \DateTime();
            $monthEndDate
                ->setDate($year, $month, $day)
                ->setTime(23, 59, 59)
                ->modify('last day of this month')
            ;

            // Mouvements du mois
            $mouvements = $mouvementRepository->findByDateAndCategorie($categorie, $monthStartDate, $monthEndDate, 'ASC', $compte);

            $montants[$year][$month] = 0;

            foreach ($mouvements as $mouvement) {
                $montant = $mouvement->getMontant();
                $montants[$year][$month] += $montant;
            }
        }

        return $montants;
    }

    /**
     * Calcule le montant mensuel moyen des mouvements d'une catégorie,
     * compris entre deux dates incluses.
     *
     * @param ?Categorie $categorie
     * @param \DateTime  $dateStart Date de début, incluse.
     * @param \DateTime  $dateEnd   Date de fin, incluse.
     * @param ?Compte    $compte    Un compte, facultatif.
     *
     * @return float Le montant mensuel moyen des mouvements de la catégorie
     */
    public function getAverageMonthlyMontantsByCategorie(?Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd, ?Compte $compte = null): float
    {
        $monthlyMontants = $this->getMonthlyMontantsByCategorie($categorie, $dateStart, $dateEnd, $compte);

        $average = 0;
        $monthCount = 0;

        foreach ($monthlyMontants as $months) {
            foreach ($months as $montant) {
                $average += $montant;
                $monthCount++;
            }
        }

        $average /= $monthCount;

        return $average;
    }

    /**
     * Compte la distance parcourue entre deux dates incluses,
     * en se basant sur les pleins de carburant.
     * Le calcul de la distance est pessimiste car il ne tient pas compte des
     * pleins non terminés ou qui étaient déjà entamés au début de la période.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     */
    public function getDistanceByDate(\DateTime $dateStart, \DateTime $dateEnd): float
    {
        /** @var PleinRepository $pleinRepository */
        $pleinRepository = $this->doctrine->getRepository('ComptesBundle:Plein');

        // Tous les pleins entre ces deux dates
        $pleins = $pleinRepository->findByDate($dateStart, $dateEnd, 'ASC');

        // Les pleins classés par véhicule, ne sert que de flag
        $pleinsByVehicule = [];

        // Distance parcourue
        $distance = 0;

        foreach ($pleins as $plein) {
            $vehicule = $plein->getVehicule();
            $vehiculeID = $vehicule->getId();

            // Ne calcule pas la distance pour le premier plein de ce véhicule
            if (!isset($pleinsByVehicule[$vehiculeID])) {
                $pleinsByVehicule[$vehiculeID] = [];
            } else {
                $pleinsByVehicule[$vehiculeID][] = $plein;
                $distance += $plein->getDistanceParcourue();
            }
        }

        return $distance;
    }
}
