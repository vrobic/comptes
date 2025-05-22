<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Repository\PleinRepository;
use Doctrine\ORM\EntityManager;
use ComptesBundle\Service\ConfigurationLoader;
use ComptesBundle\Entity\Plein;

/**
 * Décrit un handler d'import de pleins.
 *
 * Il doit être surchargé par une classe concrète implémentant la méthode parse.
 */
abstract class AbstractPleinsImportHandler implements PleinsImportHandlerInterface
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
     * @var array<string, Plein>
     */
    private $pleins;

    /**
     * Tableau de classification.
     *
     * Les pleins parsés valides.
     *
     * @var array<string, Plein>
     */
    private $validPleins;

    /**
     * Tableau de classification.
     *
     * Les pleins pour lesquels une vérification manuelle est nécessaire
     * avant de procéder à leur import, dans le cas d'une suspicion de doublon
     * par exemple.
     *
     * @var array<string, Plein>
     */
    private $waitingPleins;

    /**
     * Constructeur.
     */
    public function __construct(EntityManager $entityManager, ConfigurationLoader $configurationLoader)
    {
        // Injection de dépendances
        $this->em = $entityManager;

        // Chargement de la configuration
        $configuration = $configurationLoader->load('import');
        $this->configuration = $configuration['handlers']['pleins'];

        // Tableaux de classification
        $this->pleins = [];
        $this->validPleins = [];
        $this->waitingPleins = [];
    }

    /**
     * Parse le fichier et remplit différents tableaux
     * de classification des pleins :
     *      - tous les pleins,
     *      - les valides,
     *      - ceux à valider.
     */
    public function parse(\SplFileObject $file): void
    {
        throw new \Exception("Le handler d'import de pleins doit implémenter la méthode parse.");
    }

    /**
     * Ajoute un plein à la liste de tous les pleins parsés.
     */
    private function addPlein(Plein $plein): self
    {
        $hash = $plein->getHash();
        $this->pleins[$hash] = $plein;

        return $this;
    }

    /**
     * Récupère tous les pleins parsés.
     *
     * @return array<string, Plein>
     */
    public function getPleins(): array
    {
        return $this->pleins;
    }

    /**
     * Ajoute un plein à la liste des pleins parsés valides.
     */
    private function addValidPlein(Plein $plein): self
    {
        $hash = $plein->getHash();
        $this->validPleins[$hash] = $plein;

        return $this;
    }

    /**
     * Récupère les pleins parsés valides.
     *
     * @return array<string, Plein>
     */
    public function getValidPleins(): array
    {
        return $this->validPleins;
    }

    /**
     * Ajoute un plein à la liste des pleins parsés pour lesquels
     * une vérification manuelle est nécessaire.
     */
    private function addWaitingPlein(Plein $plein): self
    {
        $hash = $plein->getHash();
        $this->waitingPleins[$hash] = $plein;

        return $this;
    }

    /**
     * Récupère les pleins parsés pour lesquels
     * une vérification manuelle est nécessaire.
     *
     * @return array<string, Plein>
     */
    public function getWaitingPleins(): array
    {
        return $this->waitingPleins;
    }

    /**
     * Détermine la classification d'un plein, parmi :
     *      - self::VALID
     *      - self::WAITING
     */
    protected function getClassification(Plein $plein): int
    {
        // Recherche d'un éventuel doublon
        $criteria = [
            'date' => $plein->getDate(),
            'vehicule' => $plein->getVehicule(),
        ];
        /** @var PleinRepository $pleinRepository */
        $pleinRepository = $this->em->getRepository('ComptesBundle:Plein');
        $similarPlein = $pleinRepository->findOneBy($criteria);

        if ($similarPlein instanceof Plein) {
            $classification = self::WAITING;
        } else {
            $classification = self::VALID;
        }

        return $classification;
    }

    /**
     * Insère le plein dans les tableaux de classification.
     */
    protected function classify(Plein $plein, int $classification): void
    {
        // Classification du plein
        switch ($classification) {
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
