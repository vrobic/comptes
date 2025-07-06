<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

/**
 * Catégorie de mouvements.
 */
final class Categorie
{
    // @todo : supprimer les getters, setters et ajouter des readonly
    public function __construct(
        private readonly CategorieId $id,
        private string $nom, // nom de la catégorie
        private ?CategorieId $categorieParente, // catégorie parente
        private CategorieIdCollection $categoriesFilles, // catégories filles
        private ?int $rang, // rang d'affichage
    ) {
    }

    public function __toString(): string
    {
        return $this->getNom();
    }

    public function getId(): CategorieId
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
    public function setCategorieParente(?CategorieId $categorieParente = null): self
    {
        $this->categorieParente = $categorieParente;

        return $this;
    }

    /**
     * Récupère la catégorie parente.
     */
    public function getCategorieParente(): ?CategorieId
    {
        return $this->categorieParente;
    }

    /**
     * Récupère les catégories filles.
     */
    public function getCategoriesFilles(): CategorieIdCollection
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
