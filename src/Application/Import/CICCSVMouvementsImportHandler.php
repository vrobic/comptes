<?php

declare(strict_types=1);

namespace App\Application\Import;

/**
 * Implémente un handler CSV d'import de mouvements de la banque CIC.
 *
 * La banque CIC appartenant au groupe Crédit Mutuel, leurs exports sont identiques.
 */
class CICCSVMouvementsImportHandler extends CMCSVMouvementsImportHandler
{
    private const string HANDLER_ID = 'cic.csv';

    public function supports(string $handlerId): bool
    {
        return self::HANDLER_ID === $handlerId;
    }
}
