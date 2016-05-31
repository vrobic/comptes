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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getWord();
    }

    /**
     * Définit le mot.
     *
     * @param string $word
     *
     * @return Keyword
     */
    public function setWord($word)
    {
        $this->word = strtolower($word);

        return $this;
    }

    /**
     * Récupère le mot.
     *
     * @return string
     */
    public function getWord()
    {
        return $this->word;
    }

    /**
     * Définit la catégorie du mot-clé.
     *
     * @param Categorie $categorie
     *
     * @return Keyword
     */
    public function setCategorie(Categorie $categorie)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Récupère la catégorie du mot-clé.
     *
     * @return Categorie
     */
    public function getCategorie()
    {
        return $this->categorie;
    }
}
