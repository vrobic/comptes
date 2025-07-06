<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Categorie\Categorie;

/**
 * Mot-clé de catégorie.
 */
final class Keyword
{
    // @todo : supprimer les getters, setters et ajouter des readonly
    public function __construct(
        // @todo : supprimer $id car $word est unique
        private readonly KeywordId $id,
        private string $word, // mot
        private Categorie $categorie, // catégorie utilisant ce mot-clé
    ) {
    }

    public function __toString(): string
    {
        return $this->word;
    }

    public function getId(): KeywordId
    {
        return $this->id;
    }

    /**
     * Définit le mot.
     */
    public function setWord(string $word): self
    {
        $this->word = strtolower($word);

        return $this;
    }

    /**
     * Récupère le mot.
     */
    public function getWord(): string
    {
        return $this->word;
    }

    /**
     * Définit la catégorie du mot-clé.
     */
    public function setCategorie(Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Récupère la catégorie du mot-clé.
     */
    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }
}
