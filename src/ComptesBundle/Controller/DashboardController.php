<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Service\StatsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur du tableau de bord.
 */
class DashboardController extends Controller
{
    /**
     * Affichage du dashboard.
     */
    public function indexAction(): Response
    {
        /**
         * Fournisseur de statistiques.
         *
         * @var StatsProvider $statsProvider
         */
        $statsProvider = $this->container->get('comptes_bundle.stats.provider');

        // Balance mensuelle sur le mois dernier
        $dateStart = new \DateTime();
        $dateStart->modify('first day of last month midnight');
        $dateEnd = new \DateTime();
        $dateEnd->modify('first day of this month midnight');
        $monthlyBalanceOverLastMonth = $statsProvider->getMonthlyBalance($dateStart, $dateEnd);

        // Balance mensuelle sur les trois derniers mois
        $dateStart = new \DateTime();
        $dateStart->modify('first day of -3 months midnight');
        $dateEnd = new \DateTime();
        $dateEnd->modify('first day of this month midnight');
        $monthlyBalanceOverLastQuarter = $statsProvider->getMonthlyBalance($dateStart, $dateEnd);

        // Balance mensuelle sur les douze derniers mois
        $dateStart = new \DateTime();
        $dateStart->modify('last year first day of this month midnight'); // Depuis un an, en début de mois
        $dateEnd = new \DateTime();
        $dateEnd->modify('first day of this month midnight'); // Jusqu'à la fin du mois dernier
        $monthlyBalanceOverLastYear = $statsProvider->getMonthlyBalance($dateStart, $dateEnd);

        // Période sur laquelle baser le calcul de la distance parcourue
        $dateStart = new \DateTime();
        $dateStart->modify('-1 month midnight'); // Depuis un mois
        $dateEnd = new \DateTime();

        // Distance parcourue
        $distanceForOneMonth = $statsProvider->getDistanceByDate($dateStart, $dateEnd);

        return $this->render(
            'ComptesBundle:Dashboard:index.html.twig',
            [
                'monthly_balance_over_last_month' => $monthlyBalanceOverLastMonth,
                'monthly_balance_over_last_quarter' => $monthlyBalanceOverLastQuarter,
                'monthly_balance_over_last_year' => $monthlyBalanceOverLastYear,
                'distance_for_once_month' => $distanceForOneMonth,
            ]
        );
    }
}
