<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

/**
 * Catégorie de mouvements.
 */
final class Categorie
{
    /**
     * @param int[] $categoriesFilles
     */
    public function __construct(
        private readonly int $id,
        private string $nom, // nom de la catégorie
        private ?int $categorieParente, // catégorie parente
        private array $categoriesFilles, // catégories filles
        private ?int $rang, // rang d'affichage
    ) {
    }

    public function __toString(): string
    {
        return $this->getNom();
    }

    public function getId(): int
    {
        return $this->id;
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
    public function setCategorieParente(?int $categorieParente = null): self
    {
        $this->categorieParente = $categorieParente;

        return $this;
    }

    /**
     * Récupère la catégorie parente.
     */
    public function getCategorieParente(): ?int
    {
        return $this->categorieParente;
    }

    /**
     * Associe une catégorie fille.
     */
    public function addCategorieFille(int $categorie): self
    {
        $this->categoriesFilles[] = $categorie;

        return $this;
    }

    /**
     * Dissocie une catégorie fille.
     */
    public function removeCategorieFille(int $categorie): self
    {
        // @todo

        return $this;
    }

    /**
     * Dissocie toutes les catégories filles.
     */
    public function removeCategoriesFilles(): self
    {
        $this->categoriesFilles = [];

        return $this;
    }

    /**
     * Récupère les catégories filles.
     *
     * @return int[]
     */
    public function getCategoriesFilles(): array
    {
        return $this->categoriesFilles;
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
