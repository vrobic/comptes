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
     * @return Response
     */
    public function indexAction()
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $categorieRepository = $doctrine->getRepository('ComptesCoreBundle:Categorie');
        $mouvementRepository = $doctrine->getRepository('ComptesCoreBundle:Mouvement');

        // Toutes les catégories
        $categories = $categorieRepository->findAll();

        // Montant total des mouvements par catégorie
        $montants = array();

        // Montant cumulé de tous les mouvements, et des mouvements catégorisés
        $montantTotal = $mouvementRepository->getMontantTotal();
        $montantTotalCategorise = 0;

        foreach ($categories as $categorie)
        {
            $categorieID = $categorie->getId();

            $montantTotalCategorie = $categorieRepository->getMontantTotal($categorie);
            $montantTotalCategorise += $montantTotalCategorie;
            $montants[$categorieID] = $montantTotalCategorie;
        }

        // Montant total des mouvements non catégorisés
        $montantTotalNonCategorise = $montantTotal - $montantTotalCategorise;

        return $this->render(
            'ComptesCoreBundle:Categorie:index.html.twig',
            array(
                'categories' => $categories,
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
