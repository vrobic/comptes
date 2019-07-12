<?php

namespace ComptesBundle\Entity;

use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Plein de carburant.
 */
class Plein
{
    use IdentifiableTrait,
        DateTrait;

    /**
     * Véhicule.
     *
     * @var Vehicule
     */
    protected $vehicule;

    /**
     * Distance parcourue depuis le plein précédent.
     *
     * @var string
     */
    protected $distanceParcourue;

    /**
     * Volume du plein, en litres de carburant.
     *
     * @var string
     */
    protected $quantite;

    /**
     * Prix du litre de carburant, en euros.
     *
     * @var string
     */
    protected $prixLitre;

    /**
     * Montant du plein, en euros.
     *
     * @var string
     */
    protected $montant;

    /**
     * Constructeur.
     *
     * @return Plein
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
        $date = $this->getDate()->format('d/m/Y');
        $vehicule = $this->getVehicule();
        $quantite = $this->getQuantite();
        $montant = $this->getMontant();

        return "$date $vehicule {$quantite}L {$montant}€";
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
     * Définit le véhicule.
     *
     * @param Vehicule $vehicule
     *
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
     *
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
     *
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
     *
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
     *
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

    /**
     * Valide le plein pour le moteur de validation.
     *
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    public function validate(ExecutionContextInterface $context)
    {
        $violations = array();

        if ($this->getDate() > new \DateTime()) {
            $violations[] = "La date du plein doit se situer dans le passé.";
        }

        if ($this->getDistanceParcourue() < 0) {
            $violations[] = "La distance parcourue doit être supérieure ou égale à 0.";
        }

        if ($this->getQuantite() < 0) {
            $violations[] = "La quantité de carburant doit être supérieure ou égale à 0.";
        }

        if ($this->getPrixLitre() < 0) {
            $violations[] = "Le prix du litre de carburant doit être supérieur ou égal à 0.";
        }

        if ($this->getMontant() != round($this->getPrixLitre() * $this->getQuantite(), 2)) { // @codingStandardsIgnoreLine
            $violations[] = "Le montant du plein ne correspond pas aux prix du litre et quantité saisis.";
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
