<?php

declare(strict_types=1);

namespace App\Domain\Compte;

use App\Domain\Mouvement\Mouvement;

/**
 * Compte bancaire.
 */
final class Compte
{
    public function __construct(
        private readonly int $id,
        private string $nom, // nom du compte
        private string $numero, // numéro du compte
        private string $banque, // domiciliation du compte
        private int $plafond, // plafond du compte, en euros
        private float $soldeInitial, // solde initial du compte en euros, avant le premier mouvement rentré dans l'application
        private readonly float $solde, // solde (cumul de tous les mouvements)
        private \DateTime $dateOuverture, // date d'ouverture du compte
        private ?\DateTime $dateFermeture, // date de fermeture éventuelle du compte
        private ?int $rang, // rang d'affichage
    ) {
    }

    public function __toString(): string
    {
        return $this->nom;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Définit le nom du compte.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom du compte.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Définit le numéro du compte.
     */
    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Récupère le numéro du compte.
     */
    public function getNumero(): string
    {
        return $this->numero;
    }

    /**
     * Définit la domiciliation du compte.
     */
    public function setBanque(string $banque): self
    {
        $this->banque = $banque;

        return $this;
    }

    /**
     * Récupère la domiciliation du compte.
     */
    public function getBanque(): string
    {
        return $this->banque;
    }

    /**
     * Définit le plafond du compte.
     *
     * @param int $plafond la valeur 0 correspond à l'absence de plafond
     */
    public function setPlafond(int $plafond): self
    {
        $this->plafond = $plafond;

        return $this;
    }

    /**
     * Récupère le plafond du compte.
     * La valeur 0 correspond à l'absence de plafond.
     */
    public function getPlafond(): int
    {
        return $this->plafond;
    }

    /**
     * Indique si le plafond du compte est atteint.
     */
    public function isPlafondAtteint(): bool
    {
        $plafond = $this->getPlafond();
        $solde = $this->getSolde();

        $atteint = $solde >= $plafond;

        return $atteint;
    }

    /**
     * Dissocie un mouvement du compte.
     */
    public function removeMouvement(Mouvement $mouvement): self
    {
        // @todo

        return $this;
    }

    /**
     * Définit le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     *
     * @param float $soldeInitial le solde initial du compte, en euros
     */
    public function setSoldeInitial(float $soldeInitial): self
    {
        $this->soldeInitial = $soldeInitial;

        return $this;
    }

    /**
     * Récupère le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     */
    public function getSoldeInitial(): float
    {
        return $this->soldeInitial;
    }

    /**
     * Récupère le solde du compte.
     */
    public function getSolde(): float
    {
        return $this->solde;
    }

    /**
     * Calcule le solde du compte à une date.
     */
    public function getSoldeOnDate(\DateTime $date): float
    {
        $dateOuverture = $this->getDateOuverture();
        $solde = $date >= $dateOuverture ? $this->getSoldeInitial() : 0.;

        $mouvements = $this->getMouvements(); // @todo : rebrancher

        foreach ($mouvements as $mouvement) {
            $mouvementDate = $mouvement->getDate();

            if ($mouvementDate >= $date) {
                continue;
            }

            $montant = $mouvement->getMontant();
            $solde += $montant;
        }

        return $solde;
    }

    /**
     * Définit la date d'ouverture du compte.
     */
    public function setDateOuverture(\DateTime $dateOuverture): self
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    /**
     * Récupère la date d'ouverture du compte.
     */
    public function getDateOuverture(): \DateTime
    {
        return $this->dateOuverture;
    }

    /**
     * Définit la date de fermeture du compte.
     */
    public function setDateFermeture(?\DateTime $dateFermeture): self
    {
        $this->dateFermeture = $dateFermeture;

        return $this;
    }

    /**
     * Récupère la date de fermeture du compte.
     */
    public function getDateFermeture(): ?\DateTime
    {
        return $this->dateFermeture;
    }

    /**
     * Définit le rang d'affichage du compte.
     */
    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du compte.
     */
    public function getRang(): ?int
    {
        return $this->rang;
    }

    /**
     * Valide le compte pour le moteur de validation.
     *
     * @todo : rebrancher
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

        if ($this->getPlafond() < 0) {
            $violations[] = "Le plafond du compte doit être supérieur ou égal à 0. La valeur 0 indique l'absence de plafond.";
        }

        if ($this->getDateOuverture() > new \DateTime()) {
            $violations[] = "La date d'ouverture du compte doit être située dans le passé.";
        }

        if ($this->getDateFermeture() instanceof \DateTime) {
            if ($this->getDateFermeture() > new \DateTime()) {
                $violations[] = 'La date de fermeture du compte doit être située dans le passé.';
            }
            if ($this->getDateFermeture() < $this->getDateOuverture()) {
                $violations[] = "La date de fermeture doit être postérieure ou égale à celle d'ouverture.";
            }
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
