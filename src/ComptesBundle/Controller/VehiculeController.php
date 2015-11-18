<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur des véhicules.
 */
class VehiculeController extends Controller
{
    /**
     * Liste des véhicules.
     *
     * @return Response
     */
    public function indexAction()
    {
        $vehiculeRepository = $this->getDoctrine()->getRepository('ComptesBundle:Vehicule');

        // Tous les véhicules
        $vehicules = $vehiculeRepository->findAll();

        return $this->render(
            'ComptesBundle:Vehicule:index.html.twig',
            array(
                'vehicules' => $vehicules,
            )
        );
    }
}
