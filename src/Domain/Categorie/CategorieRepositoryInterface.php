<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\Compte\CompteId;

interface CategorieRepositoryInterface
{
    public function findAll(): CategorieParCategorieIdMap;

    public function find(CategorieId $categorieId): ?Categorie;

    /**
     * Calcule le montant cumulé des mouvements d'une catégorie, entre deux dates.
     *
     * @param \DateTimeImmutable $dateStart Date de début, incluse
     * @param \DateTimeImmutable $dateEnd   Date de fin, incluse
     */
    public function getMontantTotalByDate(
        CategorieId $categorieId,
        \DateTimeImmutable $dateStart,
        \DateTimeImmutable $dateEnd,
        ?CompteId $compteId = null,
    ): float;

    public function getCategoriesFillesRecursive(CategorieId $categorieId): CategorieIdCollection;

    public function save(Categorie ...$categories): void;

    public function delete(CategorieId ...$ids): void;
}
