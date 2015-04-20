<?php

namespace ComptesBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use ComptesBundle\Entity\Categorie;

/**
 * Fournisseur de statistiques.
 */
class StatsProvider
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Configuration des statistiques,
     *
     * @var array
     */
    protected $configuration;

    /**
     * Constructeur.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        // Injection de dépendances
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();

        // Chargement de la configuration
        $configurationLoader = $container->get('comptes_bundle.configuration.loader');
        $configuration = $configurationLoader->load('stats.yml');
        $this->configuration = $configuration;
    }

    /**
     * Calcule la balance mensuelle moyenne (crédit/débit) de tous les mouvements
     * compris entre deux dates, incluses.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd Date de fin, incluse.
     * @return float
     */
    public function getMonthlyBalance(\DateTime $dateStart, \DateTime $dateEnd)
    {
        // Repositories
        $doctrine = $this->container->get('doctrine');
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Nombre de mois entre les deux dates
        $diff = $dateStart->diff($dateEnd);
        $monthsCount = $diff->y*12 + $diff->m + $diff->d / 30;

        if ($monthsCount < 1)
        {
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
     * Calcule le montant total mensuel des mouvements d'une catégorie,
     * compris entre deux dates incluses.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd Date de fin, incluse.
     * @return array Les montants des mouvements, classés par mois.
     */
    public function getMonthlyMontantsByCategorie(Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd)
    {
        // Repositories
        $doctrine = $this->container->get('doctrine');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Les montants totaux mensuels des mouvements de la catégorie
        $montants = array();

        // Tous les mois entre et sur les deux dates
        $interval = \DateInterval::createFromDateString('1 month');
        $periods = new \DatePeriod($dateStart, $interval, $dateEnd);

        // Chaque mois de la période
        foreach ($periods as $date)
        {
            $year = $date->format('Y');
            $month = $date->format('m');
            $day = $date->format('d');

            $monthStartDate = new \DateTime();
            $monthStartDate->setDate($year, $month, $day);
            $monthStartDate->modify('first day of this month');

            $monthEndDate = new \DateTime();
            $monthEndDate->setDate($year, $month, $day);
            $monthEndDate->modify('last day of this month');

            // Mouvements du mois
            $mouvements = $mouvementRepository->findByDateAndCategorie($categorie, $monthStartDate, $monthEndDate);

            $montants["$year-$month"] = 0;

            foreach ($mouvements as $mouvement)
            {
                $montant = $mouvement->getMontant();
                $montants["$year-$month"] += $montant;
            }
        }

        return $montants;
    }

    /**
     * Compte la distance parcourue entre deux dates incluses,
     * en se basant sur les pleins de carburant.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd Date de fin, incluse.
     * @return float
     */
    public function getDistanceByDate(\DateTime $dateStart, \DateTime $dateEnd)
    {
        // Repositories
        $doctrine = $this->container->get('doctrine');
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');

        // Tous les pleins entre ces deux dates
        $pleins = $pleinRepository->findByDate($dateStart, $dateEnd);

        // Distance parcourue
        $distance = 0;

        foreach ($pleins as $plein)
        {
            $distance += $plein->getDistanceParcourue();
        }

        return $distance;
    }
}