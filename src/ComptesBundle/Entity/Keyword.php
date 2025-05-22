<?php

namespace ComptesBundle\Entity;

/**
 * Mot-clé de catégorie.
 */
class Keyword
{
    use IdentifiableTrait;

    /**
     * Mot.
     *
     * @var string
     */
    protected $word;

    /**
     * Catégorie utilisant ce mot-clé.
     *
     * @var Categorie
     */
    protected $categorie;

    /**
     * Méthode toString.
     */
    public function __toString(): string
    {
        return $this->getWord();
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
