<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Repository\CompteRepository;
use ComptesBundle\Entity\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ComptesBundle\Entity\Mouvement;

/**
 * Contrôleur des comptes bancaires.
 */
class CompteController extends Controller
{
    /**
     * Liste des comptes bancaires.
     */
    public function indexAction(): Response
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        /** @var CompteRepository $compteRepository */
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Tous les comptes
        $comptes = $compteRepository->findAll();

        // Tous les mouvements
        $mouvements = $mouvementRepository->findBy([], array('date' => 'ASC'));
        $firstMouvement = reset($mouvements) ?: null;
        $lastMouvement = end($mouvements) ?: null;

        // Versements initiaux, à prendre en compte pour le calcul du solde cumulé
        $versementsInitiaux = [];

        foreach ($comptes as $key => $compte) {
            $soldeInitial = $compte->getSoldeInitial();

            if ($soldeInitial > 0) {
                $compteID = $compte->getId();
                $dateOuverture = $compte->getDateOuverture();

                $versementsInitiaux[$compteID] = array(
                    'date' => $dateOuverture,
                    'montant' => $soldeInitial,
                );
            }
        }

        // On intercale les versements initiaux sous forme de faux mouvements
        foreach ($mouvements as $key => $mouvement) {
            $date = $mouvement->getDate();
            $compte = $mouvement->getCompte();
            $compteID = $compte->getId();

            if (isset($versementsInitiaux[$compteID]) && $date >= $versementsInitiaux[$compteID]['date']) {
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
        usort($mouvements, function (Mouvement $mouvementA, Mouvement $mouvementB): int {
            $dateA = $mouvementA->getDate();
            $dateB = $mouvementB->getDate();

            return $dateA > $dateB ? 1 : -1;
        });

        return $this->render(
            'ComptesBundle:Compte:index.html.twig',
            [
                'comptes' => $comptes,
                'mouvements' => $mouvements,
                'first_mouvement' => $firstMouvement,
                'last_mouvement' => $lastMouvement,
            ]
        );
    }

    /**
     * Affichage d'un compte bancaire.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException Si la période de dates est invalide.
     */
    public function showAction(Request $request): Response
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        /** @var CompteRepository $compteRepository */
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');
        /** @var CompteRepository $categorieRepository */
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');

        // Le compte bancaire
        $compteID = $request->get('compte_id');
        /** @var ?Compte $compte */
        $compte = $compteRepository->find($compteID);

        if (!($compte instanceof Compte)) {
            throw $this->createNotFoundException("Le compte bancaire $compteID n'existe pas.");
        }

        // Filtre sur la période
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } elseif ($compte->getDateFermeture() instanceof \DateTime) { // Si le compte est clôturé, du début à la fin de sa vie
            $dateStart = $compte->getDateOuverture();
            $dateEnd = $compte->getDateFermeture();
        } else { // Sinon, le mois courant en entier
            list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime) || $dateStart > $dateEnd) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("La période de dates est invalide.");
        }

        $dateFilter = [
            'start' => $dateStart,
            'end' => $dateEnd,
        ];

        // Tous les mouvements de la période
        $mouvements = $mouvementRepository->findByCompteAndDate($compte, $dateFilter['start'], $dateFilter['end']);

        // Toutes les catégories de mouvements
        $categories = $categorieRepository->findAll();

        // Solde du compte en début de période
        if (isset($mouvements[0])) {
            $firstMouvement = $mouvements[0];
            $firstMouvementDate = $firstMouvement->getDate();
            $soldeStart = $compte->getSoldeOnDate($firstMouvementDate);
        } else {
            $soldeStart = 0;
        }

        // Balance des mouvements
        $balance = $compteRepository->getBalanceByMouvements($mouvements);

        return $this->render(
            'ComptesBundle:Compte:show.html.twig',
            [
                'compte' => $compte,
                'date_filter' => $dateFilter,
                'mouvements' => $mouvements,
                'categories' => $categories,
                'solde_start' => $soldeStart,
                'balance' => $balance,
            ]
        );
    }
}
