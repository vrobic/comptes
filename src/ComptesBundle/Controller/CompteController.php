<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ComptesBundle\Entity\Mouvement;

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
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Tous les comptes
        $comptes = $compteRepository->findAll();

        // Tous les mouvements
        $mouvements = $mouvementRepository->findBy(array(), array('date' => 'ASC'));
        $firstMouvement = reset($mouvements) ?: null;
        $lastMouvement = end($mouvements) ?: null;

        // Versements initiaux, à prendre en compte pour le calcul du solde cumulé
        $versementsInitiaux = array();

        foreach ($comptes as $key => $compte)
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

        // Les faux mouvements peuvent avoir été intercalés au mauvais endroit
        usort($mouvements, function($mouvementA, $mouvementB) {
            $dateA = $mouvementA->getDate();
            $dateB = $mouvementB->getDate();
            return $dateA > $dateB;
        });

        // Suppression des comptes fermés
        foreach ($comptes as $key => $compte)
        {
            $dateFermeture = $compte->getDateFermeture();

            if ($dateFermeture !== null)
            {
                unset($comptes[$key]);
            }
        }

        return $this->render(
            'ComptesBundle:Compte:index.html.twig',
            array(
                'comptes' => $comptes,
                'mouvements' => $mouvements,
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement
            )
        );
    }

    /**
     * Affichage d'un compte bancaire.
     *
     * @param Request $request
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException Si la période de dates est invalide.
     */
    public function showAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');

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
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        }
        else // Par défaut, le mois courant en entier
        {
            list ($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!$dateStart || !$dateEnd || $dateStart > $dateEnd)
        {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("La période de dates est invalide.");
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
            'ComptesBundle:Compte:show.html.twig',
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
