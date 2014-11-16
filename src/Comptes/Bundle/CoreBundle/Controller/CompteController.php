<?php

namespace Comptes\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Comptes\Bundle\CoreBundle\Entity\Mouvement;

class CompteController extends Controller
{
    /**
     * Liste des comptes bancaires.
     *
     * @return Response
     */
    public function indexAction()
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $compteRepository = $doctrine->getRepository('ComptesCoreBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesCoreBundle:Mouvement');

        // Tous les comptes
        $comptes = $compteRepository->findAll();

        // Tous les mouvements
        $mouvements = $mouvementRepository->findBy(array(), array('date' => 'ASC'));

        // Versements initiaux, à prendre en compte pour le calcul du solde cumulé
        $versementsInitiaux = array();

        foreach ($comptes as $compte)
        {
            $soldeInitial = $compte->getSoldeInitial();

            if ($soldeInitial > 0)
            {
                $compteID = $compte->getId();
                $dateOuverture = $compte->getDateOuverture();

                $versementsInitiaux[$compteID] = array(
                    'date' => $dateOuverture,
                    'montant' => $soldeInitial
                );
            }
        }

        // On intercale les versements initiaux sous forme de faux mouvements
        foreach ($mouvements as $key => $mouvement)
        {
            $date = $mouvement->getDate();
            $compte = $mouvement->getCompte();
            $compteID = $compte->getId();

            if (isset($versementsInitiaux[$compteID]) && $date >= $versementsInitiaux[$compteID]['date'])
            {
                $fakeMouvement = new Mouvement();
                $fakeMouvement->setCompte($compte);
                $fakeMouvement->setDate($versementsInitiaux[$compteID]['date']);
                $fakeMouvement->setMontant($versementsInitiaux[$compteID]['montant']);
                $fakeMouvement->setDescription("Versement initial");

                array_splice($mouvements, $key, 0, array($fakeMouvement));

                // Le versement initial a été pris en compte
                unset($versementsInitiaux[$compteID]);
            }
        }

        return $this->render(
            'ComptesCoreBundle:Compte:index.html.twig',
            array(
                'comptes' => $comptes,
                'mouvements' => $mouvements
            )
        );
    }

    /**
     * Affichage d'un compte bancaire.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception Si la période de dates est invalide.
     */
    public function showAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $compteRepository = $doctrine->getRepository('ComptesCoreBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesCoreBundle:Mouvement');
        $categorieRepository = $doctrine->getRepository('ComptesCoreBundle:Categorie');

        // Le compte bancaire
        $compteID = $request->get('compte_id');
        $compte = $compteRepository->find($compteID);

        if (!$compte)
        {
            throw $this->createNotFoundException("Le compte bancaire $compteID n'existe pas.");
        }

        // Filtre sur la période
        if ($request->get('date_filter'))
        {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 00:00:00");
        }
        else // Par défaut, le mois courant en entier
        {
            list ($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 00:00:00");
        }

        if (!$dateStart || !$dateEnd)
        {
            throw new \Exception("La période de dates est invalide.");
        }

        $dateFilter = array(
            'start' => $dateStart,
            'end' => $dateEnd
        );

        // Tous les mouvements de la période
        $mouvements = $mouvementRepository->findByCompteAndDate($compte, $dateFilter['start'], $dateFilter['end']);

        // Toutes les catégories de mouvements
        $categories = $categorieRepository->findAll();

        // Solde du compte en début de période
        if (isset($mouvements[0]))
        {
            $firstMouvement = $mouvements[0];
            $firstMouvementDate = $firstMouvement->getDate();
            $soldeStart = $compte->getSoldeOnDate($firstMouvementDate);
        }
        else
        {
            $soldeStart = 0;
        }

        // Balance des mouvements
        $balance = $compteRepository->getBalanceByMouvements($mouvements);

        return $this->render(
            'ComptesCoreBundle:Compte:show.html.twig',
            array(
                'compte' => $compte,
                'date_filter' => $dateFilter,
                'mouvements' => $mouvements,
                'categories' => $categories,
                'solde_start' => $soldeStart,
                'balance' => $balance
            )
        );
    }
}
