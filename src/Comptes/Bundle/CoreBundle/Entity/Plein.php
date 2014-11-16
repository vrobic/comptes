<?php

namespace Comptes\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Comptes\Bundle\CoreBundle\Entity\Vehicule;

/**
 * Plein de carburant.
 *
 * @ORM\Table(name="pleins")
 * @ORM\Entity(repositoryClass="Comptes\Bundle\CoreBundle\Entity\Repository\PleinRepository")
 */
class Plein
{
    /**
     * Identifiant du plein.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Date du plein.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    protected $date;

    /**
     * Véhicule.
     *
     * @var Vehicule
     *
     * @ORM\ManyToOne(targetEntity="Vehicule", inversedBy="pleins", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    protected $vehicule;

    /**
     * Distance parcourue depuis le plein précédent.
     *
     * @var string
     *
     * @ORM\Column(name="distance_parcourue", type="decimal", precision=6, scale=2)
     */
    protected $distanceParcourue;

    /**
     * Volume du plein, en litres de carburant.
     *
     * @var string
     *
     * @ORM\Column(name="quantite", type="decimal", precision=5, scale=2)
     */
    protected $quantite;

    /**
     * Prix du litre de carburant, en euros.
     *
     * @var string
     *
     * @ORM\Column(name="prix_litre", type="decimal", precision=4, scale=3)
     */
    protected $prixLitre;

    /**
     * Montant du plein, en euros.
     *
     * @var string
     *
     * @ORM\Column(name="montant", type="decimal", precision=8, scale=2)
     */
    protected $montant;

    /**
     * Constructeur.
     *
     * @return Mouvement
     */
    public function __construct()
    {
        // Date du jour par défaut
        $this->date = new \DateTime();

        return $this;
    }

    /**
     * Méthode toString.
     *
     * @return string
     */
    public function __toString()
    {
        $date = $this->getDate()->format("d/m/Y");
        $vehicule = $this->getVehicule();
        $quantite = $this->getQuantite();

        return "$date $vehicule {$quantite}L";
    }

    /**
     * Renvoie un hash MD5 de l'objet, utilisé pour l'identifier
     * dans les imports tant que son id n'est pas encore défini.
     *
     * @return string
     */
    public function getHash()
    {
        $string = (string) $this;
        $hash = md5($string);

        return $hash;
    }

    /**
     * Récupère l'identifiant du mouvement.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Définit la date du plein.
     *
     * @param \DateTime $date
     * @return Mouvement
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Récupère la date du plein.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Définit le véhicule.
     *
     * @param Vehicule $vehicule
     * @return Plein
     */
    public function setVehicule(Vehicule $vehicule)
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    /**
     * Récupère le véhicule.
     *
     * @return Vehicule
     */
    public function getVehicule()
    {
        return $this->vehicule;
    }

    /**
     * Définit la distance parcourue depuis le plein précédent.
     *
     * @param string $distanceParcourue
     * @return Plein
     */
    public function setDistanceParcourue($distanceParcourue)
    {
        $this->distanceParcourue = $distanceParcourue;

        return $this;
    }

    /**
     * Récupère la distance parcourue depuis le plein précédent.
     *
     * @return string
     */
    public function getDistanceParcourue()
    {
        return $this->distanceParcourue;
    }

    /**
     * Définit le volume du plein, en litres de carburant.
     *
     * @param string $quantite
     * @return Plein
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Récupère le volume du plein, en litres de carburant.
     *
     * @return string
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * Définit le prix du litre du carburant, en euros.
     *
     * @param string $prixLitre
     * @return Plein
     */
    public function setPrixLitre($prixLitre)
    {
        $this->prixLitre = $prixLitre;

        return $this;
    }

    /**
     * Récupère le prix du litre du carburant, en euros.
     *
     * @return string
     */
    public function getPrixLitre()
    {
        return $this->prixLitre;
    }

    /**
     * Définit le montant du plein, en euros.
     *
     * @param string $montant
     * @return Plein
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Récupère le montant du plein, en euros.
     *
     * @return float
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Calcule la consommation en carburant du plein, en litres au 100km.
     *
     * @return float
     */
    public function getConsommation()
    {
        $quantite = $this->getQuantite();
        $distanceParcourue = $this->getDistanceParcourue();

        $consommation = $distanceParcourue > 0 ? $quantite * 100 / $distanceParcourue : 0;

        return $consommation;
    }

    /**
     * Calcule l'autonomie estimée du véhicule pour le plein.
     *
     * @return float
     */
    public function getAutonomie()
    {
        // Consommation en carburant du plein, au 100km
        $consommation = $this->getConsommation();

        // Capacité du réservoir du véhicule, en litres
        $vehicule = $this->getVehicule();
        $capacite = $vehicule->getCapaciteReservoir();

        $autonomie = $consommation > 0 ? $capacite * 100 / $consommation : 0;

        return $autonomie;
    }
}