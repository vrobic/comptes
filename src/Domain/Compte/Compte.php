<?php

declare(strict_types=1);

namespace App\Domain\Compte;

use App\Domain\Temps\Date;
use App\Domain\Temps\Periode;

/**
 * Compte bancaire.
 */
final class Compte
{
    public function __construct(
        public readonly CompteId $id,
        public string $nom,
        public string $numero,
        public string $banque,
        public ?Plafond $plafond,
        public Solde $soldeInitial, // solde initial, avant le premier mouvement rentré dans l'application
        public readonly Solde $solde,
        public Date $dateOuverture,
        public ?Date $dateFermeture,
        public ?int $rang, // rang d'affichage
    ) {
        if ($this->plafond instanceof Plafond && !$this->plafond->estPositif()) {
            throw new \DomainException("Le plafond d'un compte bancaire doit être positif.");
        }

        if ($this->dateFermeture instanceof Date && $this->dateFermeture < $this->dateOuverture) {
            throw new \DomainException("La date de fermeture d'un compte bancaire doit être postérieure ou égale à celle d'ouverture.");
        }
    }

    /**
     * Le compte est ouvert de sa date d'ouverture à sa date de fermeture, bornes comprises.
     */
    public function estOuvertLe(Date $date): bool
    {
        return $date >= $this->dateOuverture
            && (!$this->dateFermeture instanceof Date || $date <= $this->dateFermeture);
    }

    /**
     * Le compte est ouvert pendant toute la période : à son début comme à sa fin.
     */
    public function estOuvertPendant(Periode $période): bool
    {
        return $this->estOuvertLe($période->début) && $this->estOuvertLe($période->fin);
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
