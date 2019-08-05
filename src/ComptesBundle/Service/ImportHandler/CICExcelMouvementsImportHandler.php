<?php

namespace ComptesBundle\Service\ImportHandler;

/**
 * Implémente un handler Excel d'import de mouvements de la banque CIC.
 *
 * La banque CIC appartenant au groupe Crédit Mutuel, leurs exports sont identiques.
 */
class CICExcelMouvementsImportHandler extends CMExcelMouvementsImportHandler
{
    const HANDLER_ID = 'cic.excel';
}
