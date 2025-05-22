<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Repository\MouvementRepository;
use Doctrine\ORM\EntityManager;
use ComptesBundle\Service\ConfigurationLoader;
use ComptesBundle\Service\MouvementCategorizer;
use ComptesBundle\Entity\Mouvement;

/**
 * Décrit un handler d'import de mouvements.
 *
 * Il doit être surchargé par une classe concrète implémentant la méthode parse.
 * Cette méthode doit parser le fichier d'entrée et remplir différents tableaux
 * de classification des mouvements :
 *      - tous les mouvements,
 *      - les catégorisés,
 *      - les non catégorisés,
 *      - les ambigus,
 *      - ceux à valider.
 */
abstract class AbstractMouvementsImportHandler implements MouvementsImportHandlerInterface
{
    /**
     * @internal Flag de catégorisation d'un mouvement catégorisé,
     * à importer tel quel.
     */
    const CATEGORIZED = 0;

    /**
     * @internal Flag de catégorisation d'un mouvement non catégorisé,
     * à importer tel quel.
     */
    const UNCATEGORIZED = 1;

    /**
     * @internal Flag de catégorisation d'un mouvement pour lequel
     * plusieurs catégories ont été trouvées,
     * et qui nécessite donc une validation manuelle avant d'être importé.
     */
    const AMBIGUOUS = 2;

    /**
     * @internal Flag de catégorisation d'un mouvement déjà importé,
     * nécessitant donc une validation manuelle avant d'être éventuellement réimporté.
     */
    const WAITING = 3;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MouvementCategorizer
     */
    protected $mouvementCategorizer;

    /**
     * Configuration des imports.
     *
     * @var array
     */
    protected $configuration;

    /**
     * Tous les mouvements parsés.
     *
     * @var array<string, Mouvement>
     */
    private $mouvements;

    /**
     * Tableau de classification.
     *
     * Les mouvements parsés et catégorisés.
     *
     * @var array<string, Mouvement>
     */
    private $categorizedMouvements;

    /**
     * Tableau de classification.
     *
     * Les mouvements parsés et non catégorisés.
     *
     * @var array<string, Mouvement>
     */
    private $uncategorizedMouvements;

    /**
     * Tableau de classification.
     *
     * Les mouvements pour lesquels la catégorie n'a pas pu être formellement
     * déterminée parceque leur description est ambigüe.
     * Par exemple, le mouvement "LOYER JANVIER + EAU" peut correspondre
     * aux catégories "loyer" et "charges".
     * Ces cas nécessitent un choix manuel avant de procéder à leur import.
     *
     * @var array<string, Mouvement>
     */
    private $ambiguousMouvements;

    /**
     * Tableau de classification.
     *
     * Les mouvements pour lesquels une vérification manuelle est nécessaire
     * avant de procéder à leur import, dans le cas d'une suspicion de doublon
     * par exemple.
     *
     * @var array<string, Mouvement>
     */
    private $waitingMouvements;

    /**
     * Constructeur.
     */
    public function __construct(EntityManager $entityManager, ConfigurationLoader $configurationLoader, MouvementCategorizer $mouvementCategorizer)
    {
        // Injection de dépendances
        $this->em = $entityManager;
        $this->mouvementCategorizer = $mouvementCategorizer;

        // Chargement de la configuration
        $configuration = $configurationLoader->load('import');
        $this->configuration = $configuration['handlers']['mouvements'];

        // Tableaux de classification
        $this->mouvements = [];
        $this->categorizedMouvements = [];
        $this->uncategorizedMouvements = [];
        $this->ambiguousMouvements = [];
        $this->waitingMouvements = [];
    }

    /**
     * Parse le fichier et remplit différents tableaux
     * de classification des mouvements :
     *      - tous les mouvements,
     *      - les catégorisés,
     *      - les non catégorisés,
     *      - les ambigus,
     *      - ceux à valider.
     */
    public function parse(\SplFileObject $file): void
    {
        throw new \Exception("Le handler d'import de mouvements doit implémenter la méthode parse.");
    }

    /**
     * Ajoute un mouvement à la liste de tous les mouvements parsés.
     */
    public function addMouvement(Mouvement $mouvement): self
    {
        $hash = $mouvement->getHash();
        $this->mouvements[$hash] = $mouvement;

        return $this;
    }

    /**
     * Récupère tous les mouvements parsés.
     *
     * @return array<string, Mouvement>
     */
    public function getMouvements(): array
    {
        return $this->mouvements;
    }

    /**
     * Ajoute un mouvement à la liste des mouvements parsés et catégorisés.
     */
    public function addCategorizedMouvement(Mouvement $mouvement): self
    {
        $hash = $mouvement->getHash();
        $this->categorizedMouvements[$hash] = $mouvement;

        return $this;
    }

    /**
     * Récupère les mouvements parsés et catégorisés.
     *
     * @return array<string, Mouvement>
     */
    public function getCategorizedMouvements(): array
    {
        return $this->categorizedMouvements;
    }

    /**
     * Ajoute un mouvement à la liste des mouvements parsés et non catégorisés.
     */
    public function addUncategorizedMouvement(Mouvement $mouvement): self
    {
        $hash = $mouvement->getHash();
        $this->uncategorizedMouvements[$hash] = $mouvement;

        return $this;
    }

    /**
     * Récupère les mouvements parsés et non catégorisés.
     *
     * @return array<string, Mouvement>
     */
    public function getUncategorizedMouvements(): array
    {
        return $this->uncategorizedMouvements;
    }

    /**
     * Ajoute un mouvement à la liste des mouvements parsés pour lesquels
     * la catégorie n'a pas pu être formellement déterminée.
     */
    public function addAmbiguousMouvement(Mouvement $mouvement): self
    {
        $hash = $mouvement->getHash();
        $this->ambiguousMouvements[$hash] = $mouvement;

        return $this;
    }

    /**
     * Récupère les mouvements parsés pour lesquels
     * la catégorie n'a pas pu être formellement déterminée.
     *
     * @return array<string, Mouvement>
     */
    public function getAmbiguousMouvements(): array
    {
        return $this->ambiguousMouvements;
    }

    /**
     * Ajoute un mouvement à la liste des mouvements parsés pour lesquels
     * une vérification manuelle est nécessaire.
     */
    public function addWaitingMouvement(Mouvement $mouvement): self
    {
        $hash = $mouvement->getHash();
        $this->waitingMouvements[$hash] = $mouvement;

        return $this;
    }

    /**
     * Récupère les mouvements parsés pour lesquels
     * une vérification manuelle est nécessaire.
     *
     * @return array<string, Mouvement>
     */
    public function getWaitingMouvements(): array
    {
        return $this->waitingMouvements;
    }

    /**
     * Détermine la classification d'un mouvement, parmi :
     *      - self::CATEGORIZED
     *      - self::UNCATEGORIZED
     *      - self::AMBIGUOUS
     *      - self::WAITING
     */
    protected function getClassification(Mouvement $mouvement): int
    {
        // Service de catégorisation automatique des mouvements
        $categories = $this->mouvementCategorizer->getCategories($mouvement);

        if ($categories) {
            if (count($categories) > 1) {
                $classification = self::AMBIGUOUS;
            } else {
                $mouvement->setCategorie($categories[0]);
                $classification = self::CATEGORIZED;
            }
        } else {
            $classification = self::UNCATEGORIZED;
        }

        // Recherche d'un éventuel doublon
        $criteria = [
            'date' => $mouvement->getDate(),
            'compte' => $mouvement->getCompte(),
            'montant' => $mouvement->getMontant(),
        ];
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $this->em->getRepository('ComptesBundle:Mouvement');
        $similarMouvement = $mouvementRepository->findOneBy($criteria);

        if ($similarMouvement instanceof Mouvement) {
            $classification = self::WAITING;
        }

        return $classification;
    }

    /**
     * Insère le mouvement dans les tableaux de classification.
     */
    protected function classify(Mouvement $mouvement, int $classification): void
    {
        // Classification du mouvement
        switch ($classification) {
            case self::CATEGORIZED:
                $this->addCategorizedMouvement($mouvement);
                break;
            case self::UNCATEGORIZED:
                $this->addUncategorizedMouvement($mouvement);
                break;
            case self::AMBIGUOUS:
                $this->addAmbiguousMouvement($mouvement);
                break;
            case self::WAITING:
                $this->addWaitingMouvement($mouvement);
                break;
        }

        $this->addMouvement($mouvement);
    }
}
