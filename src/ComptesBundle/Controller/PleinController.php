<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Entity\Repository\PleinRepository;
use ComptesBundle\Entity\Repository\VehiculeRepository;
use ComptesBundle\Entity\Vehicule;
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
     */
    public function indexAction(): Response
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        /** @var PleinRepository $pleinRepository */
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');
        /** @var VehiculeRepository $vehiculeRepository */
        $vehiculeRepository = $doctrine->getRepository('ComptesBundle:Vehicule');

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
            [
                'pleins' => $pleins,
                'vehicules' => $vehicules,
                'total_quantite' => $totalQuantite,
                'total_montant' => $totalMontant,
                'total_distance' => $totalDistance,
            ]
        );
    }

    /**
     * Édition de pleins par lots.
     *
     * @todo Utiliser un formulaire Symfony.
     */
    public function editAction(Request $request): Response
    {
        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        /** @var PleinRepository $pleinRepository */
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');
        /** @var VehiculeRepository $vehiculeRepository */
        $vehiculeRepository = $doctrine->getRepository('ComptesBundle:Vehicule');

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $pleinsArray = $request->get('pleins', []);

        foreach ($batchArray as $pleinID) {
            if (isset($pleinsArray[$pleinID])) {
                $pleinArray = $pleinsArray[$pleinID];
                /** @var ?Plein $plein */
                $plein = $pleinID > 0 ? $pleinRepository->find($pleinID) : new Plein(); // @todo : voir que faire du null

                switch ($action) {
                    case 'save': // Création et édition
                        // Date
                        if (isset($pleinArray['date'])) {
                            $dateString = $pleinArray['date'];
                            $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                            if (!($date instanceof \DateTime)) {
                                throw new \Exception("Date du plein invalide : $dateString");
                            }
                            $plein->setDate($date);
                        }

                        // Véhicule
                        if (isset($pleinArray['vehicule'])) {
                            $vehiculeID = $pleinArray['vehicule'];
                            /** @var ?Vehicule $vehicule */
                            $vehicule = $vehiculeID !== '' ? $vehiculeRepository->find($vehiculeID) : null;
                            $plein->setVehicule($vehicule);
                        }

                        // Distance parcourue
                        if (isset($pleinArray['distanceParcourue'])) {
                            $distanceParcourue = (float) str_replace(',', '.', $pleinArray['distanceParcourue']);
                            $plein->setDistanceParcourue($distanceParcourue);
                        }

                        // Quantité
                        if (isset($pleinArray['quantite'])) {
                            $quantite = (float) str_replace(',', '.', $pleinArray['quantite']);
                            $plein->setQuantite($quantite);
                        }

                        // Prix au litre
                        if (isset($pleinArray['prixLitre'])) {
                            $prixLitre = (float) str_replace(',', '.', $pleinArray['prixLitre']);
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
