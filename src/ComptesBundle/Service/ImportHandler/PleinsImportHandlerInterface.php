<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Plein;

interface PleinsImportHandlerInterface extends ImportHandlerInterface
{
    /** @return array<string, Plein> */
    public function getPleins(): array;

    /** @return array<string, Plein> */
    public function getValidPleins(): array;

    /** @return array<string, Plein> */
    public function getWaitingPleins(): array;

}
