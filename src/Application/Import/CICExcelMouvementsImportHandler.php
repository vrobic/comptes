<?php

declare(strict_types=1);

namespace App\Application\Import;

/**
 * Implémente un handler Excel d'import de mouvements de la banque CIC.
 *
 * La banque CIC appartenant au groupe Crédit Mutuel, leurs exports sont identiques.
 */
class CICExcelMouvementsImportHandler extends CMExcelMouvementsImportHandler
{
    protected const string HANDLER_ID = 'cic.excel';

    public function supports(string $handlerId): bool
    {
        return static::HANDLER_ID === $handlerId;
    }
}
