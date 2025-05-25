<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Keyword\Keyword;

final readonly class KeywordDenormalizer implements Denormalizer
{
    public function __construct(
        private CategorieDenormalizer $categorieDenormalizer,
    ) {
    }

    public function denormalize(array $data): Keyword
    {
        return new Keyword(
            (int) $data['id'],
            (string) $data['word'],
            $this->categorieDenormalizer->denormalize($data['categorie']),
        );
    }
}
