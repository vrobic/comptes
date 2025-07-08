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
        public int $plafond, // en euros
        public float $soldeInitial, // solde initial en euros, avant le premier mouvement rentré dans l'application
        public readonly float $solde, // solde en euros (cumul de tous les mouvements)
        public \DateTimeImmutable $dateOuverture,
        public ?\DateTimeImmutable $dateFermeture,
        public ?int $rang, // rang d'affichage
    ) {
    }

    public function __toString(): string
    {
        return $this->nom;
    }

    /**
     * Valide le compte pour le moteur de validation.
     *
     * @todo : rebrancher
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

        if ($this->plafond < 0) {
            $violations[] = "Le plafond du compte doit être supérieur ou égal à 0. La valeur 0 indique l'absence de plafond.";
        }

        if ($this->dateOuverture > new \DateTimeImmutable()) {
            $violations[] = "La date d'ouverture du compte doit être située dans le passé.";
        }

        if ($this->dateFermeture instanceof \DateTimeImmutable) {
            if ($this->dateFermeture > new \DateTimeImmutable()) {
                $violations[] = 'La date de fermeture du compte doit être située dans le passé.';
            }
            if ($this->dateFermeture < $this->dateOuverture) {
                $violations[] = "La date de fermeture doit être postérieure ou égale à celle d'ouverture.";
            }
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
