<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\Mouvement\MouvementCategorizer;
use App\Domain\Categorie\Classification;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Mouvement\MouvementsParClassification;
use App\Infrastructure\Configuration\ConfigurationLoader;

/**
 * Décrit un handler d'import de mouvements.
 *
 * Il doit être surchargé par une classe concrète implémentant la méthode parse.
 * Cette méthode doit parser le fichier d'entrée et remplir le tableau de classification des mouvements.
 */
abstract class AbstractMouvementsImportHandler implements MouvementsImportHandlerInterface
{
    /**
     * Configuration des imports.
     */
    protected array $configuration;

    public MouvementsParClassification $mouvementsParClassification {
        get {
            return $this->mouvementsParClassification;
        }
    }

    /**
     * Constructeur.
     */
    public function __construct(
        protected readonly MouvementRepositoryInterface $mouvementRepository,
        protected readonly CompteRepositoryInterface $compteRepository,
        protected readonly IdGeneratorInterface $idGenerator,
        private readonly MouvementCategorizer $mouvementCategorizer,
        ConfigurationLoader $configurationLoader,
    ) {
        $this->configuration = $configurationLoader()['import']['handlers']['mouvements'];
        $this->mouvementsParClassification = new MouvementsParClassification();
    }

    public function parse(\SplFileObject $file): void
    {
        throw new \Exception("Le handler d'import de mouvements doit implémenter la méthode parse.");
    }

    /**
     * Insère le mouvement dans le tableau de classification.
     */
    protected function classify(Mouvement $mouvement): void
    {
        $classification = $this->getClassification($mouvement);

        /** @var MouvementCollection $mouvements */
        $mouvements = $this->mouvementsParClassification->has($classification->name) ?
            $this->mouvementsParClassification->get($classification->name) :
            new MouvementCollection();

        $mouvements = $mouvements->add($mouvement);

        $this->mouvementsParClassification = $this->mouvementsParClassification
            ->remove($classification->name)
            ->add($classification->name, $mouvements);
    }

    /**
     * Détermine la classification d'un mouvement.
     */
    private function getClassification(Mouvement $mouvement): Classification
    {
        // Service de catégorisation automatique des mouvements
        $categories = $this->mouvementCategorizer->getCategories($mouvement);

        if (!$categories->isEmpty()) {
            if (count($categories) > 1) {
                $classification = Classification::AMBIGUOUS;
            } else {
                $mouvement->categorie = $categories->first();
                $classification = Classification::CATEGORIZED;
            }
        } else {
            $classification = Classification::UNCATEGORIZED;
        }

        // Recherche d'un éventuel doublon
        $similarMouvements = $this->mouvementRepository->findBy(
            maybeCategoriesIds: Maybe::nothing(),
            maybeCompteId: Maybe::from($mouvement->compte->id),
            maybeDateStart: Maybe::from($mouvement->date),
            maybeDateEnd: Maybe::from($mouvement->date),
            maybeMontant: Maybe::from($mouvement->montant),
        );

        if (!$similarMouvements->isEmpty()) {
            $classification = Classification::WAITING;
        }

        return $classification;
    }
}
