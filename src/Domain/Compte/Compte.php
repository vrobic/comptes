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
        public readonly CompteId $id,
        public string $nom,
        public string $numero,
        public string $banque,
        public ?int $plafond, // en euros
        public float $soldeInitial, // solde initial en euros, avant le premier mouvement rentré dans l'application
        public readonly float $solde, // solde en euros (cumul de tous les mouvements)
        public \DateTimeImmutable $dateOuverture,
        public ?\DateTimeImmutable $dateFermeture,
        public ?int $rang, // rang d'affichage
    ) {
        if (is_int($this->plafond) && $this->plafond <= 0) {
            throw new \DomainException("Le plafond d'un compte bancaire doit être positif.");
        }

        if ($this->dateFermeture instanceof \DateTimeImmutable && $this->dateFermeture < $this->dateOuverture) {
            throw new \DomainException("La date de fermeture d'un compte bancaire doit être postérieure ou égale à celle d'ouverture.");
        }
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
