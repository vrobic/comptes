<?php

declare(strict_types=1);

namespace App\Domain\Compte;

use App\Domain\DataStructure\Set;

/**
 * @extends Set<Compte>
 */
final class CompteCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Compte::class);
    }

    /** @param Compte $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }

    public function ouverts(): self
    {
        return $this->filter(
            static fn (Compte $compte): bool => is_null($compte->dateFermeture)
        );
    }

    public function fermés(): self
    {
        return $this->filter(
            static fn (Compte $compte): bool => $compte->dateFermeture instanceof \DateTimeInterface
        );
    }

    public function solde(): Solde
    {
        return $this->reduce(
            static fn (Solde $solde, Compte $compte): Solde => $solde->additionner($compte->solde),
            Solde::nul()
        );
    }
}
