<?php

declare(strict_types=1);

namespace App\Infrastructure\Texte;

use App\Domain\Texte\DétecterUnMotInterface;

/**
 * Cherche un mot dans un texte avec une expression régulière.
 */
final readonly class DétecterUnMot implements DétecterUnMotInterface
{
    public function __invoke(string $texte, string $mot): bool
    {
        // Le mot est cherché comme du texte, et non comme une partie de regex
        $motÉchappé = preg_quote($mot, '/');

        return (bool) preg_match("/\b$motÉchappé\b/i", $texte);
    }
}
