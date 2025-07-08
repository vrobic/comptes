<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

enum Classification
{
    case CATEGORIZED; // mouvement catégorisé, à importer tel quel
    case UNCATEGORIZED; // mouvement non catégorisé, à importer tel quel
    case AMBIGUOUS; // mouvement pour lequel plusieurs catégories ont été trouvées, et qui nécessite donc une validation manuelle avant d'être importé
    case WAITING; // mouvement déjà importé, nécessitant donc une validation manuelle avant d'être éventuellement réimporté
}
