<?php

namespace Comptes\Bundle\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Comptes\Bundle\CoreBundle\Entity\Compte;

class LoadCompteData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $comptesContent = $fixturesConfiguration['comptes'];

        foreach ($comptesContent as $compteContent)
        {
            $compte = new Compte();

            // Date d'ouverture
            $dateOuverture = new \DateTime();
            $dateOuverture->setTimestamp($compteContent['date_ouverture']);

            $compte->setNom($compteContent['nom']);
            $compte->setNumero($compteContent['numero']);
            $compte->setBanque($compteContent['banque']);
            $compte->setPlafond($compteContent['plafond']);
            $compte->setSoldeInitial($compteContent['solde_initial']);
            $compte->setDateOuverture($dateOuverture);
            $compte->setRang($compteContent['rang']);

            $manager->persist($compte);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
