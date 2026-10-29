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
        return (bool) preg_match("/\b$mot\b/i", $texte);
    }
}
