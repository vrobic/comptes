<?php

namespace Comptes\Bundle\CoreBundle\Controller;

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
        $statsProvider = $this->container->get('comptes_core.stats.provider');

        // Période sur laquelle baser le calcul des statistiques
        $dateStart = new \DateTime();
        $dateStart->modify('-1 year')->setTime(0, 0);
        $dateEnd = new \DateTime();

        // Balance mensuelle
        $monthlyBalance = $statsProvider->getMonthlyBalance($dateStart, $dateEnd);

        return $this->render(
            'ComptesCoreBundle:Dashboard:index.html.twig',
            array(
                'monthly_balance' => $monthlyBalance
            )
        );
    }
}
