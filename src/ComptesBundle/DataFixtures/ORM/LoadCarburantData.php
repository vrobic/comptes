<?php

namespace ComptesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Carburant;

class LoadCarburantData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $fixturesConfiguration = $configurationLoader->load('fixtures.yml');

        // Tableau de données
        $carburantsContent = $fixturesConfiguration['carburants'];

        foreach ($carburantsContent as $key => $carburantContent)
        {
            $carburant = new Carburant();

            $carburant->setNom($carburantContent['nom']);
            $carburant->setRang($carburantContent['rang']);

            $manager->persist($carburant);

            // Enregistre l'objet pour pouvoir le réutiliser dans les autres fixtures
            $this->addReference("carburant-$key", $carburant);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
