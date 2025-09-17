<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\Mouvement\MouvementCategorizer;
use App\Domain\Categorie\Classification;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Infrastructure\Configuration\ConfigurationLoader;

/**
 * Décrit un handler d'import de mouvements.
 */
abstract class AbstractMouvementsImportHandler implements MouvementsImportHandlerInterface
{
    /**
     * Configuration des imports.
     */
    protected array $configuration;

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
    }

    /**
     * Détermine la classification d'un mouvement.
     */
    protected function getClassification(Mouvement $mouvement): Classification
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
