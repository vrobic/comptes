<?php

declare(strict_types=1);

namespace App\Application\Mouvement;

use App\Domain\Categorie\Categorie;
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
     *
     * @todo : renvoyer une collection ?
     *
     * @return Categorie[] liste de catégories
     */
    public function getCategories(Mouvement $mouvement): array
    {
        // Tous les mots-clés de description
        $keywords = $this->keywordRepository->findAll();

        // La description du mouvement
        $description = $mouvement->getDescription();

        // Les catégories probables du mouvement
        $categories = [];

        foreach ($keywords as $keyword) {
            $word = $keyword->getWord();

            // Si le mot-clé est présent dans la description
            if (preg_match("/\b$word\b/i", $description)) {
                $categorie = $keyword->getCategorie();
                $categorieID = $categorie->getId();

                $categories[$categorieID] = $categorie; // Assure l'unicité
            }
        }

        // Reset des clés
        $categories = array_values($categories);

        return $categories;
    }
}
