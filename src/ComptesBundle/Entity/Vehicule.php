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
     * @var ?\DateTime
     */
    protected $dateVente;

    /**
     * Kilométrage du véhicule à son achat.
     *
     * @var float
     */
    protected $kilometrageAchat;

    /**
     * Kilométrage du véhicule après le premier plein rentré dans l'application.
     *
     * @var float
     */
    protected $kilometrageInitial;

    /**
     * Prix d'achat du véhicule, en euros.
     *
     * @var float
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
     * @var float
     */
    protected $capaciteReservoir;

    /**
     * Pleins de carburant.
     *
     * @var Plein[]|ArrayCollection
     */
    protected $pleins;

    /**
     * Rang d'affichage du véhicule.
     *
     * @var ?int
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
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Définit les marque et modèle du véhicule.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère les marque et modèle du véhicule.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Définit la date d'achat du véhicule.
     */
    public function setDateAchat(\DateTime $dateAchat): self
    {
        $this->dateAchat = $dateAchat;

        return $this;
    }

    /**
     * Récupère la date d'achat du véhicule.
     */
    public function getDateAchat(): \DateTime
    {
        return $this->dateAchat;
    }

    /**
     * Définit la date de revente du véhicule.
     */
    public function setDateVente(?\DateTime $dateVente): self
    {
        $this->dateVente = $dateVente;

        return $this;
    }

    /**
     * Récupère la date de revente du véhicule.
     */
    public function getDateVente(): ?\DateTime
    {
        return $this->dateVente;
    }

    /**
     * Indique si le véhicule est revendu.
     */
    public function isVendu(): bool
    {
        $dateVente = $this->getDateVente();

        $vendu = $dateVente instanceof \DateTime;

        return $vendu;
    }

    /**
     * Définit le kilométrage du véhicule à son achat.
     */
    public function setKilometrageAchat(float $kilometrageAchat): self
    {
        $this->kilometrageAchat = $kilometrageAchat;

        return $this;
    }

    /**
     * Récupère le kilométrage du véhicule à son achat.
     */
    public function getKilometrageAchat(): float
    {
        return $this->kilometrageAchat;
    }

    /**
     * Définit le kilométrage du véhicule après le premier plein rentré dans l'application.
     */
    public function setKilometrageInitial(float $kilometrageInitial): self
    {
        $this->kilometrageInitial = $kilometrageInitial;

        return $this;
    }

    /**
     * Récupère le kilométrage du véhicule après le premier plein rentré dans l'application.
     */
    public function getKilometrageInitial(): float
    {
        return $this->kilometrageInitial;
    }

    /**
     * Définit le prix d'achat du véhicule, en euros.
     */
    public function setPrixAchat(float $prixAchat): self
    {
        $this->prixAchat = $prixAchat;

        return $this;
    }

    /**
     * Récupère le prix d'achat du véhicule, en euros.
     */
    public function getPrixAchat(): float
    {
        return $this->prixAchat;
    }

    /**
     * Définit le carburant utilisé par le véhicule.
     */
    public function setCarburant(Carburant $carburant): self
    {
        $this->carburant = $carburant;

        return $this;
    }

    /**
     * Récupère le carburant utilisé par le véhicule.
     */
    public function getCarburant(): Carburant
    {
        return $this->carburant;
    }

    /**
     * Définit la capacité du réservoir, en litres.
     */
    public function setCapaciteReservoir(float $capaciteReservoir): self
    {
        $this->capaciteReservoir = $capaciteReservoir;

        return $this;
    }

    /**
     * Récupère la capacité du réservoir, en litres.
     */
    public function getCapaciteReservoir(): float
    {
        return $this->capaciteReservoir;
    }

    /**
     * Associe un plein de carburant au véhicule.
     */
    public function addPlein(Plein $plein): self
    {
        $this->pleins[] = $plein;

        return $this;
    }

    /**
     * Dissocie un plein de carburant du véhicule.
     */
    public function removePlein(Plein $plein): self
    {
        $this->pleins->removeElement($plein);

        return $this;
    }

    /**
     * Dissocie tous les pleins de carburant du véhicule.
     */
    public function removePleins(): self
    {
        $this->pleins->clear();

        return $this;
    }

    /**
     * Récupère les pleins de carburant du véhicule.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return Plein[]
     */
    public function getPleins()
    {
        $pleins = $this->pleins->toArray();

        usort($pleins, function (Plein $a, Plein $b): int {
            return $a->getDate() > $b->getDate() ? 1 : -1;
        });

        return $pleins;
    }

    /**
     * Calcule le kilométrage du véhicule.
     */
    public function getKilometrage(): float
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
     */
    public function getKilometrageAnnuel(): float
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
     */
    public function getConsommation(): float
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
     */
    public function getAutonomie(): float
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
     */
    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du véhicule.
     */
    public function getRang(): ?int
    {
        return $this->rang;
    }

    /**
     * Valide le véhicule pour le moteur de validation.
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

        if ($this->getDateAchat() > new \DateTime()) {
            $violations[] = "La date d'achat du véhicule doit être située dans le passé.";
        }

        if ($this->getDateVente() instanceof \DateTime && $this->getDateVente() > new \DateTime()) {
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
