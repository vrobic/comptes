<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

/**
 * Balance, en euros.
 */
final class Balance
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

        if ($this->estPositive()) {
            return '+'.$string;
        }

        return $string;
    }

    public static function nulle(): self
    {
        return new self(0.);
    }

    public function estPositive(): bool
    {
        return $this->montant > 0.;
    }

    public function estNégative(): bool
    {
        return $this->montant < 0.;
    }

    public function additionner(self $valeur): self
    {
        return new self($this->montant + $valeur->montant);
    }

    public function soustraire(self $valeur): self
    {
        return new self($this->montant - $valeur->montant);
    }

    public function diviser(int|float $valeur): self
    {
        if (0 === $valeur || 0. === $valeur) {
            throw new \LogicException();
        }

        return new self($this->montant / $valeur);
    }
}
