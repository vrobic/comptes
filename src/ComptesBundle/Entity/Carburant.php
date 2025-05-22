<?php

namespace ComptesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Carburant.
 */
class Carburant
{
    use IdentifiableTrait;

    /**
     * Nom commercial du carburant.
     *
     * @var string
     */
    protected $nom;

    /**
     * Véhicules utilisant ce carburant.
     *
     * @var Vehicule[]|ArrayCollection
     */
    protected $vehicules;

    /**
     * Rang d'affichage du carburant.
     *
     * @var ?int
     */
    protected $rang;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Définit le nom commercial du carburant.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom commercial du carburant.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Associe un véhicule au carburant.
     */
    public function addVehicule(Vehicule $vehicule): self
    {
        $this->vehicules[] = $vehicule;

        return $this;
    }

    /**
     * Dissocie un véhicule du carburant.
     */
    public function removeVehicule(Vehicule $vehicule): self
    {
        $this->vehicules->removeElement($vehicule);

        return $this;
    }

    /**
     * Dissocie tous les véhicules du carburant.
     */
    public function removeVehicules(): self
    {
        $this->vehicules->clear();

        return $this;
    }

    /**
     * Récupère les véhicules utilisant ce carburant.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return Vehicule[]|ArrayCollection
     */
    public function getVehicules()
    {
        return $this->vehicules;
    }

    /**
     * Définit le rang d'affichage du carburant.
     */
    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du carburant.
     */
    public function getRang(): ?int
    {
        return $this->rang;
    }
}
