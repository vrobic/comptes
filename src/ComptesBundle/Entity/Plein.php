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
     * @var float
     */
    protected $distanceParcourue;

    /**
     * Volume du plein, en litres de carburant.
     *
     * @var float
     */
    protected $quantite;

    /**
     * Prix du litre de carburant, en euros.
     *
     * @var float
     */
    protected $prixLitre;

    /**
     * Montant du plein, en euros.
     *
     * @var float
     */
    protected $montant;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        // Date du jour par défaut
        $this->date = new \DateTime();
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
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
     */
    public function getHash(): string
    {
        $string = (string) $this;
        $hash = md5($string);

        return $hash;
    }

    /**
     * Définit le véhicule.
     */
    public function setVehicule(Vehicule $vehicule): self
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    /**
     * Récupère le véhicule.
     */
    public function getVehicule(): Vehicule
    {
        return $this->vehicule;
    }

    /**
     * Définit la distance parcourue depuis le plein précédent.
     */
    public function setDistanceParcourue(float $distanceParcourue): self
    {
        $this->distanceParcourue = $distanceParcourue;

        return $this;
    }

    /**
     * Récupère la distance parcourue depuis le plein précédent.
     */
    public function getDistanceParcourue(): float
    {
        return $this->distanceParcourue;
    }

    /**
     * Définit le volume du plein, en litres de carburant.
     */
    public function setQuantite(float $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Récupère le volume du plein, en litres de carburant.
     */
    public function getQuantite(): float
    {
        return $this->quantite;
    }

    /**
     * Définit le prix du litre du carburant, en euros.
     */
    public function setPrixLitre(float $prixLitre): self
    {
        $this->prixLitre = $prixLitre;

        return $this;
    }

    /**
     * Récupère le prix du litre du carburant, en euros.
     */
    public function getPrixLitre(): float
    {
        return $this->prixLitre;
    }

    /**
     * Définit le montant du plein, en euros.
     */
    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Récupère le montant du plein, en euros.
     */
    public function getMontant(): float
    {
        return $this->montant;
    }

    /**
     * Calcule la consommation en carburant du plein, en litres au 100km.
     */
    public function getConsommation(): float
    {
        $quantite = $this->getQuantite();
        $distanceParcourue = $this->getDistanceParcourue();

        $consommation = $distanceParcourue > 0 ? $quantite * 100 / $distanceParcourue : 0;

        return $consommation;
    }

    /**
     * Calcule l'autonomie estimée du véhicule pour le plein.
     */
    public function getAutonomie(): float
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
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

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

        // @todo : supprimer ce @codingStandardsIgnoreLine
        if ($this->getMontant() != round($this->getPrixLitre() * $this->getQuantite(), 2)) { // @codingStandardsIgnoreLine
            $violations[] = "Le montant du plein ne correspond pas aux prix du litre et quantité saisis.";
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
