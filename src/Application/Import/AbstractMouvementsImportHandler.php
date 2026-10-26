<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Categorie\CategorieCollection;
use App\Domain\Categorie\Classification;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Keyword\KeywordRepositoryInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Mouvement\MouvementsParClassification;
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
        private readonly KeywordRepositoryInterface $keywordRepository,
        ConfigurationLoader $configurationLoader,
    ) {
        $this->configuration = $configurationLoader()['import']['handlers']['mouvements'];
    }

    public function parse(\SplFileObject $file): MouvementsParClassification
    {
        $mouvementsParClassification = new MouvementsParClassification();

        // Les mots-clés de description
        $keywords = $this->keywordRepository->findAll();

        foreach ($this->lireMouvements($file) as $mouvement) {
            // Les catégories probables du mouvement, d'après tous les mots-clés de description
            $categories = $keywords->trouverLesCatégories($mouvement->description);

            $classification = $this->getClassification($mouvement, $categories);

            $mouvementsParClassification = $mouvementsParClassification->ajouter($classification, $mouvement);
        }

        return $mouvementsParClassification;
    }

    /**
     * Lit les mouvements du fichier, dans leur ordre d'apparition.
     */
    abstract protected function lireMouvements(\SplFileObject $file): MouvementCollection;

    /**
     * Détermine la classification d'un mouvement selon ses catégories probables.
     */
    private function getClassification(Mouvement $mouvement, CategorieCollection $categories): Classification
    {
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
