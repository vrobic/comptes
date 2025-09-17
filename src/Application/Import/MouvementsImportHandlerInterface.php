<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Mouvement\MouvementsParClassification;

interface MouvementsImportHandlerInterface
{
    public function supports(string $handlerId): bool;

    /**
     * Parse le fichier pour remplir le tableau classification des mouvements.
     */
    public function parse(\SplFileObject $file): MouvementsParClassification;
}
