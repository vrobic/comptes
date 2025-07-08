<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Application\Mouvement\MouvementCategorizer;
use App\Domain\Categorie\Classification;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementsParHash;
use App\Domain\Mouvement\MouvementsParHashParClassification;
use App\Infrastructure\Configuration\ConfigurationLoader;
use App\Infrastructure\Repository\CompteRepository;
use App\Infrastructure\Repository\MouvementRepository;

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

    public MouvementsParHashParClassification $mouvementsParHashParClassification {
        get {
            return $this->mouvementsParHashParClassification;
        }
    }

    /**
     * Constructeur.
     */
    public function __construct(
        protected readonly MouvementRepository $mouvementRepository,
        protected readonly CompteRepository $compteRepository,
        protected readonly IdGeneratorInterface $idGenerator,
        private readonly MouvementCategorizer $mouvementCategorizer,
        ConfigurationLoader $configurationLoader,
    ) {
        $this->configuration = $configurationLoader()['import']['handlers']['mouvements'];
        $this->mouvementsParHashParClassification = new MouvementsParHashParClassification();
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

        /** @var MouvementsParHash $mouvementsParHash */
        $mouvementsParHash = $this->mouvementsParHashParClassification->has($classification->name) ?
            $this->mouvementsParHashParClassification->get($classification->name) :
            new MouvementsParHash();

        // @todo : permettre de classifier plusieurs mouvements identiques (donc ayant le même hash)
        $mouvementsParHash = $mouvementsParHash->add($mouvement->getHash(), $mouvement);

        $this->mouvementsParHashParClassification = $this->mouvementsParHashParClassification
            ->remove($classification->name)
            ->add($classification->name, $mouvementsParHash);
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
            categoriesIds: Maybe::nothing(),
            compteId: Maybe::from($mouvement->compte->id),
            dateStart: Maybe::from($mouvement->date),
            dateEnd: Maybe::from($mouvement->date),
            montant: Maybe::from($mouvement->montant),
        );

        if (!$similarMouvements->isEmpty()) {
            $classification = Classification::WAITING;
        }

        return $classification;
    }
}
