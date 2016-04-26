<?php

namespace ComptesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ComptesBundle\Entity\Compte;

/**
 * La fixture qui crée les comptes bancaires.
 */
class LoadCompteData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $comptesContent = $fixturesConfiguration['comptes'];

        foreach ($comptesContent as $compteContent) {

            $compte = new Compte();

            // Date d'ouverture
            $dateOuverture = new \DateTime();
            $dateOuverture->setTimestamp($compteContent['date_ouverture']);

            // Date de fermeture facultative
            if ($compteContent['date_fermeture'] !== null) {
                $dateFermeture = new \DateTime();
                $dateFermeture->setTimestamp($compteContent['date_fermeture']);
            } else {
                $dateFermeture = null;
            }

            $compte->setNom($compteContent['nom']);
            $compte->setNumero($compteContent['numero']);
            $compte->setBanque($compteContent['banque']);
            $compte->setPlafond($compteContent['plafond']);
            $compte->setSoldeInitial($compteContent['solde_initial']);
            $compte->setDateOuverture($dateOuverture);
            $compte->setDateFermeture($dateFermeture);
            $compte->setRang($compteContent['rang']);

            $manager->persist($compte);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
