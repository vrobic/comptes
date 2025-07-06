<?php

declare(strict_types=1);

namespace App\Application\Mouvement;

use App\Domain\Categorie\CategorieCollection;
use App\Domain\Mouvement\Mouvement;
use App\Infrastructure\Repository\KeywordRepository;

/**
 * Service permettant de catégoriser automatiquement
 * les mouvements lorsqu'ils sont importés.
 */
final readonly class MouvementCategorizer
{
    public function __construct(private KeywordRepository $keywordRepository)
    {
    }

    /**
     * Trouve les catégories probables d'un mouvement.
     */
    public function getCategories(Mouvement $mouvement): CategorieCollection
    {
        // Tous les mots-clés de description
        $keywords = $this->keywordRepository->findAll();

        // La description du mouvement
        $description = $mouvement->getDescription();

        // Les catégories probables du mouvement
        $categories = new CategorieCollection();

        foreach ($keywords as $keyword) {
            $word = $keyword->getWord();

            // Si le mot-clé est présent dans la description
            if (preg_match("/\b$word\b/i", $description)) {
                $categories = $categories->add($keyword->getCategorie());
            }
        }

        return $categories;
    }
}
