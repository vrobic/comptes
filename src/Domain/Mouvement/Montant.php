<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

/**
 * Montant, en euros.
 */
final class Montant
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
}
