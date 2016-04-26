<?php

namespace ComptesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Carburant;

/**
 * La fixture qui crée les carburants.
 */
class LoadCarburantData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $carburantsContent = $fixturesConfiguration['carburants'];

        foreach ($carburantsContent as $key => $carburantContent) {

            $carburant = new Carburant();

            $carburant->setNom($carburantContent['nom']);
            $carburant->setRang($carburantContent['rang']);

            $manager->persist($carburant);

            // Enregistre l'objet pour pouvoir le réutiliser dans les autres fixtures
            $id = $key + 1; // Son identifiant
            $this->addReference("carburant-$id", $carburant);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
