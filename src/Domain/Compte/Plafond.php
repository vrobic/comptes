<?php

declare(strict_types=1);

namespace App\Domain\Compte;

/**
 * Plafond, en euros.
 */
final class Plafond
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

    public function estPositif(): bool
    {
        return $this->montant > 0.;
    }
}
