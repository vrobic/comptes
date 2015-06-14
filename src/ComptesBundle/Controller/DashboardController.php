<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    /**
     * Affichage du dashboard.
     *
     * @return Response
     */
    public function indexAction()
    {
        // Fournisseur de statistiques
        $statsProvider = $this->container->get('comptes_bundle.stats.provider');

        // Période sur laquelle baser le calcul de la balance mensuelle
        $dateStart = new \DateTime();
        $dateStart->modify('last year first day of this month midnight'); // Depuis un an, en début de mois
        $dateEnd = new \DateTime();
        $dateEnd->modify('first day of this month midnight'); // Jusqu'à la fin du mois dernier

        // Balance mensuelle
        $monthlyBalance = $statsProvider->getMonthlyBalance($dateStart, $dateEnd);

        // Période sur laquelle baser le calcul de la distance parcourue
        $dateStart = new \DateTime();
        $dateStart->modify('-1 month midnight'); // Depuis un mois
        $dateEnd = new \DateTime();

        // Distance parcourue
        $distanceForOneMonth = $statsProvider->getDistanceByDate($dateStart, $dateEnd);

        return $this->render(
            'ComptesBundle:Dashboard:index.html.twig',
            array(
                'monthly_balance' => $monthlyBalance,
                'distance_for_once_month' => $distanceForOneMonth
            )
        );
    }
}
