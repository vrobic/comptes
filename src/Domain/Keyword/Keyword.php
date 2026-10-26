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

    /**
     * Indique si le mot-clé est présent dans une description.
     */
    public function estPrésentDans(string $description): bool
    {
        return (bool) preg_match("/\b$this->word\b/i", $description);
    }

    public function __toString(): string
    {
        return $this->word;
    }
}
