<?php

namespace Comptes\Bundle\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Comptes\Bundle\CoreBundle\Entity\Vehicule;

class LoadVehiculeData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container=null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // Chargement de la configuration
        $configurationLoader = $this->container->get('comptes_core.configuration.loader');
        $fixturesConfiguration = $configurationLoader->load('fixtures.yml');

        // Tableau de données
        $vehiculesContent = $fixturesConfiguration['vehicules'];

        foreach ($vehiculesContent as $vehiculeContent)
        {
            $vehicule = new Vehicule();

            // Date d'achat
            if ($vehiculeContent['date_achat'] !== null)
            {
                $dateAchat = new \DateTime();
                $dateAchat->setTimestamp($vehiculeContent['date_achat']);
            }
            else
            {
                $dateAchat = null;
            }

            // Date de vente
            if ($vehiculeContent['date_vente'] !== null)
            {
                $dateVente = new \DateTime();
                $dateVente->setTimestamp($vehiculeContent['date_vente']);
            }
            else
            {
                $dateVente = null;
            }

            // Carburant
            $carburantID = $vehiculeContent['carburant'];
            $carburant = $this->getReference("carburant-$carburantID");

            $vehicule->setNom($vehiculeContent['nom']);
            $vehicule->setDateAchat($dateAchat);
            $vehicule->setDateVente($dateVente);
            $vehicule->setKilometrageAchat($vehiculeContent['kilometrage_achat']);
            $vehicule->setKilometrageInitial($vehiculeContent['kilometrage_initial']);
            $vehicule->setPrixAchat($vehiculeContent['prix_achat']);
            $vehicule->setCarburant($carburant);
            $vehicule->setCapaciteReservoir($vehiculeContent['capacite_reservoir']);
            $vehicule->setRang($vehiculeContent['rang']);

            $manager->persist($vehicule);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
