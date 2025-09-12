<?php

declare(strict_types=1);

namespace App\Domain\Compte;

/**
 * Solde, en euros.
 */
final class Solde
{
    public function __construct(public float $montant)
    {
    }

    public function __toString(): string
    {
        $string = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY)
            ->formatCurrency($this->montant, 'EUR');

        if (false === $string) {
            throw new \RuntimeException();
        }

        return $string;
    }

    public static function nul(): self
    {
        return new self(0.);
    }

    // Utilisé en Twig
    public function additionner(self $valeur): self
    {
        return new self($this->montant + $valeur->montant);
    }
}
