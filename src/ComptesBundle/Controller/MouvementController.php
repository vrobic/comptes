<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ComptesBundle\Entity\Mouvement;

/**
 * Contrôleur des mouvements bancaires.
 */
class MouvementController extends Controller
{
    /**
     * Édition de mouvements bancaires par lots.
     *
     * @todo Utiliser un formulaire Symfony.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', array());
        $mouvementsArray = $request->get('mouvements', array());

        foreach ($batchArray as $mouvementID) {

            if (isset($mouvementsArray[$mouvementID])) {

                $mouvementArray = $mouvementsArray[$mouvementID];
                $mouvement = $mouvementID > 0 ? $mouvementRepository->find($mouvementID) : new Mouvement();

                switch ($action) {

                    case 'save': // Création et édition

                        // Date
                        if (isset($mouvementArray['date'])) {
                            $dateString = $mouvementArray['date'];
                            $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                            $mouvement->setDate($date);
                        }

                        // Catégorie
                        if (isset($mouvementArray['categorie'])) {
                            $categorieID = $mouvementArray['categorie'];
                            $categorie = $categorieID !== '' ? $categorieRepository->find($categorieID) : null;
                            $mouvement->setCategorie($categorie);
                        }

                        // Compte
                        if (isset($mouvementArray['compte'])) {
                            $compteID = $mouvementArray['compte'];
                            $compte = $compteID !== '' ? $compteRepository->find($compteID) : null;
                            $mouvement->setCompte($compte);
                        }

                        // Montant
                        if (isset($mouvementArray['montant'])) {
                            $montant = $mouvementArray['montant'];
                            $mouvement->setMontant($montant);
                        }

                        // Description
                        if (isset($mouvementArray['description'])) {
                            $description = $mouvementArray['description'];
                            $mouvement->setDescription($description);
                        }

                        $manager->persist($mouvement);

                        break;

                    case 'delete': // Suppression

                        $manager->remove($mouvement);

                        break;
                }
            }
        }

        $manager->flush();

        // URL de redirection
        $redirectURL = $request->get('redirect_url');

        return $this->redirect($redirectURL);
    }
}
