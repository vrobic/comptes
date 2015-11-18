<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ComptesBundle\Entity\Plein;

/**
 * Contrôleur des pleins de carburant.
 */
class PleinController extends Controller
{
    /**
     * Liste des pleins.
     *
     * @return Response
     */
    public function indexAction()
    {
        // Repositories
        $pleinRepository = $this->getDoctrine()->getRepository('ComptesBundle:Plein');
        $vehiculeRepository = $this->getDoctrine()->getRepository('ComptesBundle:Vehicule');

        // Tous les pleins
        $pleins = $pleinRepository->findAll();

        // Tous les vehicules
        $vehicules = $vehiculeRepository->findAll();

        // Totaux
        $totalQuantite = 0;
        $totalMontant = 0;
        $totalDistance = 0;

        foreach ($pleins as $plein) {

            $quantite = $plein->getQuantite();
            $totalQuantite += $quantite;

            $montant = $plein->getMontant();
            $totalMontant += $montant;

            $distanceParcourue = $plein->getDistanceParcourue();
            $totalDistance += $distanceParcourue;
        }

        return $this->render(
            'ComptesBundle:Plein:index.html.twig',
            array(
                'pleins' => $pleins,
                'vehicules' => $vehicules,
                'total_quantite' => $totalQuantite,
                'total_montant' => $totalMontant,
                'total_distance' => $totalDistance,
            )
        );
    }

    /**
     * Édition de pleins par lots.
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
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');
        $vehiculeRepository = $doctrine->getRepository('ComptesBundle:Vehicule');

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', array());
        $pleinsArray = $request->get('pleins', array());

        foreach ($batchArray as $pleinID) {

            if (isset($pleinsArray[$pleinID])) {

                $pleinArray = $pleinsArray[$pleinID];
                $plein = $pleinID > 0 ? $pleinRepository->find($pleinID) : new Plein();

                switch ($action) {

                    case 'save': // Création et édition

                        // Date
                        if (isset($pleinArray['date'])) {
                            $dateString = $pleinArray['date'];
                            $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                            $plein->setDate($date);
                        }

                        // Véhicule
                        if (isset($pleinArray['vehicule'])) {
                            $vehiculeID = $pleinArray['vehicule'];
                            $vehicule = $vehiculeID !== '' ? $vehiculeRepository->find($vehiculeID) : null;
                            $plein->setVehicule($vehicule);
                        }

                        // Distance parcourue
                        if (isset($pleinArray['distanceParcourue'])) {
                            $distanceParcourue = $pleinArray['distanceParcourue'];
                            $plein->setDistanceParcourue($distanceParcourue);
                        }

                        // Quantité
                        if (isset($pleinArray['quantite'])) {
                            $quantite = $pleinArray['quantite'];
                            $plein->setQuantite($quantite);
                        }

                        // Prix au litre
                        if (isset($pleinArray['prixLitre'])) {
                            $prixLitre = $pleinArray['prixLitre'];
                            $plein->setPrixLitre($prixLitre);
                        }

                        // Montant
                        $quantite = $plein->getQuantite();
                        $prixLitre = $plein->getPrixLitre();
                        $montant = $quantite * $prixLitre;
                        $plein->setMontant($montant);

                        $manager->persist($plein);

                        break;

                    case 'delete': // Suppression

                        $manager->remove($plein);

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
