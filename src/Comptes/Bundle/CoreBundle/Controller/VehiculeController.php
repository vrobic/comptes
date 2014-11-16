<?php

namespace Comptes\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class VehiculeController extends Controller
{
    /**
     * Liste des véhicules.
     *
     * @return Response
     */
    public function indexAction()
    {
        $vehiculeRepository = $this->getDoctrine()->getRepository('ComptesCoreBundle:Vehicule');

        // Tous les véhicules
        $vehicules = $vehiculeRepository->findAll();

        return $this->render(
            'ComptesCoreBundle:Vehicule:index.html.twig',
            array(
                'vehicules' => $vehicules
            )
        );
    }
}
