<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;

final readonly class CategorieDenormalizer implements Denormalizer
{
    public function denormalize(array $data): Categorie
    {
        return new Categorie(
            new CategorieId((string) $data['id']),
            (string) $data['nom'],
            is_string($data['categorie_parente_id']) ? new CategorieId($data['categorie_parente_id']) : null,
            array_reduce(
                is_string($data['categories_filles']) ?
                    explode(',', $data['categories_filles']) :
                    [],
                static fn (CategorieIdCollection $categoriesFilles, string $categorieFille): CategorieIdCollection => $categoriesFilles->add(new CategorieId($categorieFille)),
                new CategorieIdCollection()
            ),
            is_int($data['rang']) ? $data['rang'] : null,
        );
    }
}
