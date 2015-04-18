<?php

namespace ComptesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use ComptesBundle\Entity\Vehicule;

/**
 * Carburant.
 *
 * @ORM\Table(name="carburants")
 * @ORM\Entity(repositoryClass="ComptesBundle\Entity\Repository\CarburantRepository")
 */
class Carburant
{
    /**
     * Identifiant du carburant.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Nom commercial du carburant.
     *
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    protected $nom;

    /**
     * Véhicules utilisant ce carburant.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Vehicule", mappedBy="carburant", cascade={"persist"})
     * @ORM\OrderBy({"rang" = "ASC"})
     */
    protected $vehicules;

    /**
     * Rang d'affichage du carburant.
     *
     * @var integer
     *
     * @ORM\Column(name="rang", type="integer", nullable=true)
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
     * Récupère l'identifiant du carburant.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Définit le nom commercial du carburant.
     *
     * @param string $nom
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
     */
    public function removeVehicule(Vehicule $vehicule)
    {
        $this->vehicules->removeElement($vehicule);
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
     * @param integer $rang
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
     * @return integer
     */
    public function getRang()
    {
        return $this->rang;
    }
}