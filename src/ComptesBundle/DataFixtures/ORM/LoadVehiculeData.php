<?php

namespace ComptesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Vehicule;

/**
 * La fixture qui crée les véhicules.
 */
class LoadVehiculeData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Chargement de la configuration
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $fixturesConfiguration = $configurationLoader->load('fixtures');

        // Tableau de données
        $vehiculesContent = $fixturesConfiguration['vehicules'];

        foreach ($vehiculesContent as $vehiculeContent) {
            $vehicule = new Vehicule();

            // Date d'achat
            if ($vehiculeContent['date_achat'] !== null) {
                $dateAchat = new \DateTime();
                $dateAchat->setTimestamp($vehiculeContent['date_achat']);
            } else {
                $dateAchat = null;
            }

            // Date de vente
            if ($vehiculeContent['date_vente'] !== null) {
                $dateVente = new \DateTime();
                $dateVente->setTimestamp($vehiculeContent['date_vente']);
            } else {
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
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
