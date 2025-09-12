<?php

namespace App\Tests;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\Plafond;
use App\Domain\Compte\Solde;
use App\Domain\Mouvement\Montant;

final class GenererInstance
{
    public static function compte(
        string $id = '2274b61c-dfbb-4c1b-b9f6-d6fb6506d8ba',
        string $nom = 'Compte courant',
        string $numero = '12345',
        string $banque = 'Crédit Mutuel',
        ?float $plafond = null,
        float $soldeInitial = 0,
        float $solde = 293.,
        string $dateOuverture = '2000-01-01',
        ?string $dateFermeture = null,
        ?int $rang = null,
    ): Compte
    {
        return new Compte(
            new CompteId($id),
            $nom,
            $numero,
            $banque,
            is_float($plafond) ? new Plafond($plafond) : null,
            new Solde($soldeInitial),
            new Solde($solde),
            new \DateTimeImmutable($dateOuverture),
            is_string($dateFermeture) ? new \DateTimeImmutable($dateFermeture) : null,
            $rang,
        );
    }

    public static function catégorie(
        string $id = 'd0c60a73-b561-4842-b41e-09de23b37680',
        string $nom = 'Sport',
        ?string $catégorieParente = null,
        ?int $rang = null,
    ): Categorie
    {
        return new Categorie(
            new CategorieId($id),
            $nom,
            $catégorieParente,
            new CategorieIdCollection(),
            $rang
        );
    }
}
