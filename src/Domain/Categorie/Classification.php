<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

enum Classification
{
    case CATEGORIZED; // mouvement catégorisé, à importer tel quel
    case UNCATEGORIZED; // mouvement non catégorisé, à importer tel quel
    case AMBIGUOUS; // mouvement pour lequel plusieurs catégories ont été trouvées, et qui nécessite donc une validation manuelle avant d'être importé
    case WAITING; // mouvement déjà importé, nécessitant donc une validation manuelle avant d'être éventuellement réimporté

    /**
     * Classe un mouvement d'après ses catégories probables, et selon qu'il a été détecté comme doublon.
     */
    public static function déterminer(CategorieCollection $categories, bool $doublon): self
    {
        if ($doublon) {
            return self::WAITING;
        }

        return match (count($categories)) {
            0 => self::UNCATEGORIZED,
            1 => self::CATEGORIZED,
            default => self::AMBIGUOUS,
        };
    }
}
