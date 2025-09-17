<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

interface CategorieRepositoryInterface
{
    public function findAll(): CategorieCollection;

    public function find(CategorieId $categorieId): ?Categorie;

    public function getCategoriesFillesRecursive(CategorieId $categorieId): CategorieIdCollection;

    public function save(Categorie ...$categories): void;

    public function delete(CategorieId ...$ids): void;
}
