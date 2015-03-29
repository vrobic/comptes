<?php

namespace Comptes\Bundle\CoreBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\ORM\EntityManager;
use Comptes\Bundle\CoreBundle\Entity\Plein;

/**
 * Décrit un handler d'import de pleins.
 *
 * Il doit être surchargé par une classe concrète implémentant la méthode parse.
 * Cette méthode doit parser le fichier d'entrée et remplir différents tableaux
 * de classification des pleins :
 *      - tous les pleins,
 *      - les valides,
 *      - ceux à valider.
 */
abstract class PleinsImportHandler implements ImportHandler
{
    /**
     * @internal Flag de catégorisation d'un plein valide,
     * à importer tel quel.
     */
    const VALID = 0;
    
    /**
     * @internal Flag de catégorisation d'un plein déjà importé,
     * nécessitant donc une validation manuelle avant d'être éventuellement réimporté.
     */
    const WAITING = 1;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Configuration des imports.
     *
     * @var array
     */
    protected $configuration;

    /**
     * Tous les pleins parsés.
     *
     * @var array
     */
    private $pleins;

    /**
     * Tableau de classification.
     *
     * Les pleins parsés valides.
     *
     * @var array
     */
    private $validPleins;

    /**
     * Tableau de classification.
     *
     * Les pleins pour lesquels une vérification manuelle est nécessaire
     * avant de procéder à leur import, dans le cas d'une suspicion de doublon
     * par exemple.
     *
     * @var array
     */
    private $waitingPleins;

    /**
     * Constructeur.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        // Injection de dépendances
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();

        // Chargement de la configuration
        $configurationLoader = $container->get('comptes_core.configuration.loader');
        $configuration = $configurationLoader->load('import.yml');
        $this->configuration = $configuration['handlers']['pleins'];

        // Tableaux de classification
        $this->pleins = array();
        $this->waitingPleins = array();
    }

    /**
     * Ajoute un plein à la liste de tous les pleins parsés.
     *
     * @param Plein $plein
     * @return self
     */
    public function addPlein(Plein $plein)
    {
        $this->pleins[] = $plein;

        return $this;
    }

    /**
     * Récupère tous les pleins parsés.
     *
     * @return array
     */
    public function getPleins()
    {
        return $this->pleins;
    }

    /**
     * Ajoute un plein à la liste des pleins parsés valides.
     *
     * @param Plein $plein
     * @return self
     */
    public function addValidPlein(Plein $plein)
    {
        $this->validPleins[] = $plein;

        return $this;
    }

    /**
     * Récupère les pleins parsés valides.
     *
     * @return array
     */
    public function getValidPleins()
    {
        return $this->validPleins;
    }

    /**
     * Ajoute un plein à la liste des pleins parsés pour lesquels
     * une vérification manuelle est nécessaire.
     *
     * @param Plein $plein
     * @return self
     */
    public function addWaitingPlein(Plein $plein)
    {
        $this->waitingPleins[] = $plein;

        return $this;
    }

    /**
     * Récupère les pleins parsés pour lesquels
     * une vérification manuelle est nécessaire.
     *
     * @return array
     */
    public function getWaitingPleins()
    {
        return $this->waitingPleins;
    }

    /**
     * Détermine la classification d'un plein, parmi :
     *      - self::VALID
     *      - self::WAITING
     *
     * @param Plein $plein
     * @return int
     */
    protected function getClassification(Plein $plein)
    {
        // Recherche d'un éventuel doublon
        $criteria = array(
            'date' => $plein->getDate(),
            'vehicule' => $plein->getVehicule()
        );
        $pleinRepository = $this->em->getRepository('ComptesCoreBundle:Plein');
        $similarPlein = $pleinRepository->findOneBy($criteria);

        if ($similarPlein !== null)
        {
            $classification = self::WAITING;
        }
        else
        {
            $classification = self::VALID;
        }

        return $classification;
    }

    /**
     * Insère le plein dans les tableaux de classification.
     *
     * @param Plein $plein
     * @param int $classification
     */
    protected function classify(Plein $plein, $classification)
    {
        // Classification du plein
        switch ($classification)
        {
            case self::VALID:
                $this->addValidPlein($plein);
                break;
            case self::WAITING:
                $this->addWaitingPlein($plein);
                break;
        }

        $this->addPlein($plein);
    }
}