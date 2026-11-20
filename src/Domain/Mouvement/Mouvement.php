<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;
use App\Domain\Temps\Date;

/**
 * Mouvement bancaire.
 */
class Mouvement
{
    public function __construct(
        public readonly MouvementId $id,
        public Date $date,
        public ?Categorie $categorie,
        public Compte $compte,
        public Montant $montant,
        public string $description,
    ) {
    }

    public function __toString(): string
    {
        $compte = $this->compte;
        $date = $this->date->format('d-m-Y');
        $description = $this->description;
        $montant = $this->montant;

        return "$compte $date $montant € $description";
    }
}
