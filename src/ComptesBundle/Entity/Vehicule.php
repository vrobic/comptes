<?php

namespace ComptesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Véhicule.
 */
class Vehicule
{
    use IdentifiableTrait;

    /**
     * Marque et modèle du véhicule.
     *
     * @var string
     */
    protected $nom;

    /**
     * Date d'achat du véhicule.
     *
     * @var \DateTime
     */
    protected $dateAchat;

    /**
     * Date de revente du véhicule.
     *
     * @var \DateTime
     */
    protected $dateVente;

    /**
     * Kilométrage du véhicule à son achat.
     *
     * @var string
     */
    protected $kilometrageAchat;

    /**
     * Kilométrage du véhicule après le premier plein rentré dans l'application.
     *
     * @var string
     */
    protected $kilometrageInitial;

    /**
     * Prix d'achat du véhicule, en euros.
     *
     * @var string
     */
    protected $prixAchat;

    /**
     * Carburant.
     *
     * @var Carburant
     */
    protected $carburant;

    /**
     * Capacité du réservoir, en litres.
     *
     * @var string
     */
    protected $capaciteReservoir;

    /**
     * Pleins de carburant.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $pleins;

    /**
     * Rang d'affichage du véhicule.
     *
     * @var int
     */
    protected $rang;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->pleins = new ArrayCollection();
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
     * Définit les marque et modèle du véhicule.
     *
     * @param string $nom
     *
     * @return Vehicule
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère les marque et modèle du véhicule.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Définit la date d'achat du véhicule.
     *
     * @param \DateTime $dateAchat
     *
     * @return Vehicule
     */
    public function setDateAchat($dateAchat)
    {
        $this->dateAchat = $dateAchat;

        return $this;
    }

    /**
     * Récupère la date d'achat du véhicule.
     *
     * @return \DateTime
     */
    public function getDateAchat()
    {
        return $this->dateAchat;
    }

    /**
     * Définit la date de revente du véhicule.
     *
     * @param \DateTime $dateVente
     *
     * @return Vehicule
     */
    public function setDateVente($dateVente)
    {
        $this->dateVente = $dateVente;

        return $this;
    }

    /**
     * Récupère la date de revente du véhicule.
     *
     * @return \DateTime
     */
    public function getDateVente()
    {
        return $this->dateVente;
    }

    /**
     * Indique si le véhicule est revendu.
     *
     * @return bool
     */
    public function isVendu()
    {
        $dateVente = $this->getDateVente();

        $vendu = $dateVente !== null;

        return $vendu;
    }

    /**
     * Définit le kilométrage du véhicule à son achat.
     *
     * @param string $kilometrageAchat
     *
     * @return Vehicule
     */
    public function setKilometrageAchat($kilometrageAchat)
    {
        $this->kilometrageAchat = $kilometrageAchat;

        return $this;
    }

    /**
     * Récupère le kilométrage du véhicule à son achat.
     *
     * @return string
     */
    public function getKilometrageAchat()
    {
        return $this->kilometrageAchat;
    }

    /**
     * Définit le kilométrage du véhicule après le premier plein rentré dans l'application.
     *
     * @param string $kilometrageInitial
     *
     * @return Vehicule
     */
    public function setKilometrageInitial($kilometrageInitial)
    {
        $this->kilometrageInitial = $kilometrageInitial;

        return $this;
    }

    /**
     * Récupère le kilométrage du véhicule après le premier plein rentré dans l'application.
     *
     * @return string
     */
    public function getKilometrageInitial()
    {
        return $this->kilometrageInitial;
    }

    /**
     * Définit le prix d'achat du véhicule, en euros.
     *
     * @param string $prixAchat
     *
     * @return Vehicule
     */
    public function setPrixAchat($prixAchat)
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }

    /**
     * Récupère le prix d'achat du véhicule, en euros.
     *
     * @return string
     */
    public function getPrixAchat()
    {
        return $this->prixAchat;
    }

    /**
     * Définit le carburant utilisé par le véhicule.
     *
     * @param Carburant $carburant
     *
     * @return Vehicule
     */
    public function setCarburant(Carburant $carburant)
    {
        $this->carburant = $carburant;

        return $this;
    }

    /**
     * Récupère le carburant utilisé par le véhicule.
     *
     * @return Carburant
     */
    public function getCarburant()
    {
        return $this->carburant;
    }

    /**
     * Définit la capacité du réservoir, en litres.
     *
     * @param string $capaciteReservoir
     *
     * @return Vehicule
     */
    public function setCapaciteReservoir($capaciteReservoir)
    {
        $this->capaciteReservoir = $capaciteReservoir;

        return $this;
    }

    /**
     * Récupère la capacité du réservoir, en litres.
     *
     * @return string
     */
    public function getCapaciteReservoir()
    {
        return $this->capaciteReservoir;
    }

    /**
     * Associe un plein de carburant au véhicule.
     *
     * @param Plein $plein
     *
     * @return Vehicule
     */
    public function addPlein(Plein $plein)
    {
        $this->pleins[] = $plein;

        return $this;
    }

    /**
     * Dissocie un plein de carburant du véhicule.
     *
     * @param Plein $plein
     *
     * @return Vehicule
     */
    public function removePlein(Plein $plein)
    {
        $this->pleins->removeElement($plein);

        return $this;
    }

    /**
     * Dissocie tous les pleins de carburant du véhicule.
     *
     * @return Vehicule
     */
    public function removePleins()
    {
        $this->pleins->clear();

        return $this;
    }

    /**
     * Récupère les pleins de carburant du véhicule.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPleins()
    {
        $pleins = $this->pleins->toArray();

        usort($pleins, function (Plein $a, Plein $b) {
            return $a->getDate() > $b->getDate();
        });

        return new ArrayCollection($pleins);
    }

    /**
     * Calcule le kilométrage du véhicule.
     *
     * @return float
     */
    public function getKilometrage()
    {
        $kilometrage = $this->getKilometrageInitial();

        $pleins = $this->getPleins();

        foreach ($pleins as $plein) {
            $distanceParcourue = $plein->getDistanceParcourue();
            $kilometrage += $distanceParcourue;
        }

        return $kilometrage;
    }

    /**
     * Calcule le kilométrage annuel moyen du véhicule.
     *
     * @return float
     */
    public function getKilometrageAnnuel()
    {
        // Le kilométrage parcouru
        $kilometrageAchat = $this->getKilometrageAchat();
        $kilometrageActuel = $this->getKilometrage();
        $kilometrageParcouru = $kilometrageActuel - $kilometrageAchat;

        // Nombre de jours écoulés en possession du véhicule
        $dateDebut = $this->getDateAchat();
        $dateFin = $this->isVendu() ? $this->getDateVente() : new \DateTime();
        $interval = $dateDebut->diff($dateFin);
        $days = (int) $interval->format('%r%a');

        $kilometrage = $kilometrageParcouru * 365 / $days;

        return $kilometrage;
    }

    /**
     * Calcule la consommation moyenne du véhicule, en litres au 100km.
     *
     * @return float
     */
    public function getConsommation()
    {
        $pleins = $this->getPleins();
        $pleinsCount = count($pleins);

        // Consommations au 100km, cumulées (pour calculer ensuite la moyenne)
        $consommations = 0;

        foreach ($pleins as $plein) {

            $distanceParcourue = $plein->getDistanceParcourue();
            $quantite = $plein->getQuantite();

            $consommations += $distanceParcourue > 0 ? $quantite * 100 / $distanceParcourue : 0;
        }

        $consommation = $pleinsCount > 0 ? $consommations / $pleinsCount : 0;

        return $consommation;
    }

    /**
     * Calcule l'autonomie estimée moyenne du véhicule, en km.
     *
     * @return float
     */
    public function getAutonomie()
    {
        // Consommation moyenne du véhicule, au 100km
        $consommation = $this->getConsommation();

        // Capacité du réservoir, en litres
        $capacite = $this->getCapaciteReservoir();

        $autonomie = $consommation > 0 ? $capacite * 100 / $consommation : 0;

        return $autonomie;
    }

    /**
     * Définit le rang d'affichage du véhicule.
     *
     * @param int $rang
     *
     * @return Vehicule
     */
    public function setRang($rang)
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du véhicule.
     *
     * @return int
     */
    public function getRang()
    {
        return $this->rang;
    }

    /**
     * Valide le véhicule pour le moteur de validation.
     *
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    public function validate(ExecutionContextInterface $context)
    {
        $violations = array();

        if ($this->getDateAchat() > new \DateTime()) {
            $violations[] = "La date d'achat du véhicule doit être située dans le passé.";
        }

        if ($this->getDateVente() !== null && $this->getDateVente() > new \DateTime()) {
            $violations[] = "La date de vente du véhicule doit être située dans le passé.";
        }

        if ($this->getKilometrageAchat() < 0) {
            $violations[] = "Le kilométrage à l'achat du véhicule doit être supérieur ou égal à 0.";
        }

        if ($this->getKilometrageInitial() < $this->getKilometrageAchat()) {
            $violations[] = "Le kilométrage initial doit être supérieur ou égal au kilométrage à l'achat du véhicule.";
        }

        if ($this->getCapaciteReservoir() < 0) {
            $violations[] = "La capacité du réservoir du véhicule doit être supérieure ou égale à 0.";
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
