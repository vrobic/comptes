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
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $vehicules;

    /**
     * Rang d'affichage du carburant.
     *
     * @var int
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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getNom();
    }

    /**
     * Définit le nom commercial du carburant.
     *
     * @param string $nom
     *
     * @return Carburant
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom commercial du carburant.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Associe un véhicule au carburant.
     *
     * @param Vehicule $vehicule
     *
     * @return Carburant
     */
    public function addVehicule(Vehicule $vehicule)
    {
        $this->vehicules[] = $vehicule;

        return $this;
    }

    /**
     * Dissocie un véhicule du carburant.
     *
     * @param Vehicule $vehicule
     *
     * @return Carburant
     */
    public function removeVehicule(Vehicule $vehicule)
    {
        $this->vehicules->removeElement($vehicule);

        return $this;
    }

    /**
     * Dissocie tous les véhicules du carburant.
     *
     * @return Carburant
     */
    public function removeVehicules()
    {
        $this->vehicules->clear();

        return $this;
    }

    /**
     * Récupère les véhicules utilisant ce carburant.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getVehicules()
    {
        return $this->vehicules;
    }

    /**
     * Définit le rang d'affichage du carburant.
     *
     * @param int $rang
     *
     * @return Carburant
     */
    public function setRang($rang)
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du carburant.
     *
     * @return int
     */
    public function getRang()
    {
        return $this->rang;
    }
}
