<?php

declare(strict_types=1);

namespace App\Domain\Compte;

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
        public \DateTimeImmutable $dateOuverture,
        public ?\DateTimeImmutable $dateFermeture,
        public ?int $rang, // rang d'affichage
    ) {
        if ($this->plafond instanceof Plafond && !$this->plafond->estPositif()) {
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
