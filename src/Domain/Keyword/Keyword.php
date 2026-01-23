<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Categorie\Categorie;

/**
 * Mot-clé de catégorie.
 */
final class Keyword
{
    public function __construct(
        public readonly KeywordId $id,
        public readonly string $word, // mot-clé
        public Categorie $categorie, // catégorie utilisant ce mot-clé
    ) {
    }

    public function __toString(): string
    {
        return $this->word;
    }
}
