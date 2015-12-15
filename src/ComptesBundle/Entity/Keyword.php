<?php

namespace ComptesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mot-clé de catégorie.
 *
 * @ORM\Table(name="keywords")
 * @ORM\Entity(repositoryClass="ComptesBundle\Entity\Repository\KeywordRepository")
 */
class Keyword
{
    /**
     * Identifiant du mot-clé.
     *
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Mot.
     *
     * @var string
     *
     * @ORM\Column(name="word", type="string", length=255, unique=true)
     */
    protected $word;

    /**
     * Catégorie utilisant ce mot-clé.
     *
     * @var Categorie
     *
     * @ORM\ManyToOne(targetEntity="Categorie", inversedBy="keywords", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OrderBy({"rang" = "ASC"})
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
     * Récupère l'identifiant du mot-clé.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
