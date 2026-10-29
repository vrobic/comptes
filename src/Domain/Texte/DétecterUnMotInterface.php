<?php

declare(strict_types=1);

namespace App\Domain\Texte;

interface DétecterUnMotInterface
{
    /**
     * Indique si le texte contient le mot.
     */
    public function __invoke(string $texte, string $mot): bool;
}
