<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;

/**
 * Mouvement bancaire.
 */
class Mouvement
{
    public function __construct(
        public readonly MouvementId $id,
        public \DateTimeImmutable $date,
        public ?Categorie $categorie,
        public Compte $compte,
        public float $montant, // montant en euros, positif (crédit) ou négatif (débit)
        public string $description,
    ) {
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
    {
        $compte = $this->compte;
        $date = $this->date->format('d-m-Y');
        $description = $this->description;
        $montant = number_format($this->montant, 2, ',', ' ');

        return "$compte $date {$montant} € $description";
    }

    /**
     * Renvoie un hash MD5 de l'objet, utilisé pour l'identifier
     * dans les imports tant que son id n'est pas encore défini.
     */
    public function getHash(): string
    {
        return md5((string) $this);
    }
}
