<?php

namespace Comptes\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategorieController extends Controller
{
    /**
     * Liste des catégories.
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $categorieRepository = $doctrine->getRepository('ComptesCoreBundle:Categorie');
        $mouvementRepository = $doctrine->getRepository('ComptesCoreBundle:Mouvement');

        // Toutes les catégories
        $categories = $categorieRepository->findAll();

        // Filtre sur la période
        if ($request->get('date_filter'))
        {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 00:00:00");
        }
        else // Par défaut, depuis toujours
        {
            list ($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "1900-1-1 00:00:00"); // @todo : dynamiser la date de début
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 00:00:00");
        }

        if (!$dateStart || !$dateEnd)
        {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("La période de dates est invalide.");
        }

        $dateFilter = array(
            'start' => $dateStart,
            'end' => $dateEnd
        );

        // Montant total des mouvements par catégorie
        $montants = array();

        // Montant cumulé de tous les mouvements, et des mouvements catégorisés sur la période donnée
        $montantTotal = $mouvementRepository->getMontantTotalByDate($dateFilter['start'], $dateFilter['end']);
        $montantTotalCategorise = 0;

        foreach ($categories as $categorie)
        {
            $categorieID = $categorie->getId();

            $montantTotalCategorie = $categorieRepository->getMontantTotalByDate($categorie, $dateFilter['start'], $dateFilter['end']);
            $montantTotalCategorise += $montantTotalCategorie;
            $montants[$categorieID] = $montantTotalCategorie;
        }

        // Montant total des mouvements non catégorisés
        $montantTotalNonCategorise = $montantTotal - $montantTotalCategorise;

        return $this->render(
            'ComptesCoreBundle:Categorie:index.html.twig',
            array(
                'categories' => $categories,
                'date_filter' => $dateFilter,
                'montants' => $montants,
                'montant_total_non_categorise' => $montantTotalNonCategorise
            )
        );
    }

    /**
     * Affichage d'une catégorie.
     *
     * @param Request $request
     * @return Response
     */
    public function showAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $categorieRepository = $doctrine->getRepository('ComptesCoreBundle:Categorie');
        $mouvementRepository = $doctrine->getRepository('ComptesCoreBundle:Mouvement');

        // La catégorie
        $categorieID = $request->get('categorie_id');
        $categorie = $categorieRepository->find($categorieID);

        if (!$categorie)
        {
            throw $this->createNotFoundException("La catégorie $categorieID n'existe pas.");
        }

        // Tous les mouvements de la catégorie
        $mouvements = $mouvementRepository->findByCategorie($categorie);

        // Total des mouvements
        $total = 0;

        foreach ($mouvements as $mouvement)
        {
            $montant = $mouvement->getMontant();
            $total += $montant;
        }

        return $this->render(
            'ComptesCoreBundle:Categorie:show.html.twig',
            array(
                'categorie' => $categorie,
                'mouvements' => $mouvements,
                'total' => $total
            )
        );
    }
}
