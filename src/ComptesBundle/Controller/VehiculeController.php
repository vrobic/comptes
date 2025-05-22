<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Entity\Repository\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur des véhicules.
 */
class VehiculeController extends Controller
{
    /**
     * Liste des véhicules.
     */
    public function indexAction(): Response
    {
        /** @var VehiculeRepository $vehiculeRepository */
        $vehiculeRepository = $this->getDoctrine()->getRepository('ComptesBundle:Vehicule');

        // Tous les véhicules
        $vehicules = $vehiculeRepository->findAll();

        return $this->render(
            'ComptesBundle:Vehicule:index.html.twig',
            [
                'vehicules' => $vehicules,
            ]
        );
    }
}
