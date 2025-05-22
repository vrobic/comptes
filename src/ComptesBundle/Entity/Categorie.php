<?php

namespace ComptesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Catégorie de mouvements.
 */
class Categorie
{
    use IdentifiableTrait;

    /**
     * Nom de la catégorie.
     *
     * @var string
     */
    protected $nom;

    /**
     * Catégorie parente.
     *
     * @var ?self
     */
    protected $categorieParente;

    /**
     * Catégories filles.
     *
     * @var self[]|ArrayCollection
     */
    protected $categoriesFilles;

    /**
     * Mouvements bancaires de la catégorie.
     *
     * @var Mouvement[]|ArrayCollection
     */
    protected $mouvements;

    /**
     * Mots-clés de la catégorie.
     *
     * @var Keyword[]|ArrayCollection
     */
    protected $keywords;

    /**
     * Rang d'affichage de la catégorie.
     *
     * @var ?int
     */
    protected $rang;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->mouvements = new ArrayCollection();
        $this->keywords = new ArrayCollection();
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Définit le nom de la catégorie.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom de la catégorie.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Définit la catégorie parente.
     */
    public function setCategorieParente(?self $categorieParente = null): self
    {
        $this->categorieParente = $categorieParente;

        return $this;
    }

    /**
     * Récupère la catégorie parente.
     */
    public function getCategorieParente(): ?self
    {
        return $this->categorieParente;
    }

    /**
     * Associe une catégorie fille.
     */
    public function addCategorieFille(self $categorie): self
    {
        $this->categoriesFilles->add($categorie);

        return $this;
    }

    /**
     * Dissocie une catégorie fille.
     */
    public function removeCategorieFille(self $categorie): self
    {
        $this->categoriesFilles->removeElement($categorie);

        return $this;
    }

    /**
     * Dissocie toutes les catégories filles.
     */
    public function removeCategoriesFilles(): self
    {
        $this->categoriesFilles->clear();

        return $this;
    }

    /**
     * Récupère les catégories filles.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return self[]|ArrayCollection
     */
    public function getCategoriesFilles()
    {
        return $this->categoriesFilles;
    }

    /**
     * Récupère les catégories filles récursivement.
     *
     * @todo : typer $categoriesFilles directement dans le code
     *
     * @param Categorie[] $categoriesFilles
     *
     * @return Categorie[]
     */
    public function getCategoriesFillesRecursive($categoriesFilles = []): array
    {
        foreach ($this->categoriesFilles as $categorieFille) {
            $categoriesFilles = array_merge(
                $categoriesFilles,
                $categorieFille->getCategoriesFillesRecursive([$categorieFille])
            );
        }

        return $categoriesFilles;
    }

    /**
     * Associe un mouvement à la catégorie.
     */
    public function addMouvement(Mouvement $mouvement): self
    {
        $this->mouvements[] = $mouvement;

        return $this;
    }

    /**
     * Dissocie un mouvement de la catégorie.
     */
    public function removeMouvement(Mouvement $mouvement): self
    {
        $this->mouvements->removeElement($mouvement);

        return $this;
    }

    /**
     * Dissocie tous les mouvements de la catégorie.
     */
    public function removeMouvements(): self
    {
        $this->mouvements->clear();

        return $this;
    }

    /**
     * Récupère les mouvements de la catégorie.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return Mouvement[]|ArrayCollection
     */
    public function getMouvements()
    {
        return $this->mouvements;
    }

    /**
     * Associe un mot-clé à la catégorie.
     */
    public function addKeyword(Keyword $keyword): self
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    /**
     * Dissocie un mot-clé de la catégorie.
     */
    public function removeKeyword(Keyword $keyword): self
    {
        $this->keywords->removeElement($keyword);

        return $this;
    }

    /**
     * Dissocie tous les mots-clés de la catégorie.
     */
    public function removeKeywords(): self
    {
        $this->keywords->clear();

        return $this;
    }

    /**
     * Récupère les mots-clés de la catégorie.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return Keyword[]|ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Définit le rang d'affichage de la catégorie.
     */
    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage de la catégorie.
     */
    public function getRang(): ?int
    {
        return $this->rang;
    }
}
