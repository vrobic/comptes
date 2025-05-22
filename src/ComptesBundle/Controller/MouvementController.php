<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Repository\CategorieRepository;
use ComptesBundle\Entity\Repository\CompteRepository;
use ComptesBundle\Entity\Repository\MouvementRepository;
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
     */
    public function editAction(Request $request): Response
    {
        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');
        /** @var CategorieRepository $categorieRepository */
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        /** @var CompteRepository $compteRepository */
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $mouvementsArray = $request->get('mouvements', []);

        foreach ($batchArray as $mouvementID) {
            if (isset($mouvementsArray[$mouvementID])) {
                $mouvementArray = $mouvementsArray[$mouvementID];
                /** @var ?Mouvement $mouvement */
                $mouvement = $mouvementID > 0 ? $mouvementRepository->find($mouvementID) : new Mouvement(); // @todo : voir que faire du null

                switch ($action) {
                    case 'save': // Création et édition
                        // Date
                        if (isset($mouvementArray['date'])) {
                            $dateString = $mouvementArray['date'];
                            $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                            if (!($date instanceof \DateTime)) {
                                throw new \Exception("Date du mouvement invalide : $dateString");
                            }
                            $mouvement->setDate($date);
                        }

                        // Catégorie
                        if (isset($mouvementArray['categorie'])) {
                            $categorieID = $mouvementArray['categorie'];
                            /** @var ?Categorie $categorie */
                            $categorie = $categorieID !== '' ? $categorieRepository->find($categorieID) : null; // @todo : voir que faire du null
                            $mouvement->setCategorie($categorie);
                        }

                        // Compte
                        if (isset($mouvementArray['compte'])) {
                            $compteID = $mouvementArray['compte'];
                            /** @var ?Compte $compte */
                            $compte = $compteID !== '' ? $compteRepository->find($compteID) : null; // @todo : voir que faire du null
                            $mouvement->setCompte($compte);
                        }

                        // Montant
                        if (isset($mouvementArray['montant'])) {
                            $montant = (float) str_replace(',', '.', $mouvementArray['montant']);
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
