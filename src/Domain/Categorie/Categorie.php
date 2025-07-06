<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

/**
 * Catégorie de mouvements.
 */
final class Categorie
{
    public function __construct(
        public readonly CategorieId $id,
        public string $nom,
        public ?CategorieId $categorieParente,
        public CategorieIdCollection $categoriesFilles,
        public ?int $rang, // rang d'affichage
    ) {
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
