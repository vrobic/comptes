<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\Compte\CompteId;
use App\Domain\Temps\Periode;

interface CategorieRepositoryInterface
{
    public function findAll(): CategorieParCategorieIdMap;

    public function find(CategorieId $categorieId): ?Categorie;

    public function balancePériodique(
        CategorieId $categorieId,
        Periode $période,
        ?CompteId $compteId = null,
    ): float;

    public function getCategoriesFillesRecursive(CategorieId $categorieId): CategorieIdCollection;

    public function save(Categorie ...$categories): void;

    public function delete(CategorieId ...$ids): void;
}
