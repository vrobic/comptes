<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Categorie\Categorie;

final readonly class CategorieDenormalizer implements Denormalizer
{
    public function denormalize(array $data): Categorie
    {
        return new Categorie(
            (int) $data['id'],
            (string) $data['nom'],
            !is_null($data['categorie_parente_id']) ? (int) $data['categorie_parente_id'] : null,
            array_map(
                static fn (string $categorieFille): int => (int) $categorieFille,
                is_string($data['categories_filles']) ?
                    explode(',', $data['categories_filles']) :
                    []
            ),
            is_int($data['rang']) ? $data['rang'] : null,
        );
    }
}
