<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Mouvement;

interface MouvementsImportHandlerInterface extends ImportHandlerInterface
{
    /** @return array<string, Mouvement> */
    public function getMouvements(): array;

    /** @return array<string, Mouvement> */
    public function getCategorizedMouvements(): array;

    /** @return array<string, Mouvement> */
    public function getUncategorizedMouvements(): array;

    /** @return array<string, Mouvement> */
    public function getAmbiguousMouvements(): array;

    /** @return array<string, Mouvement> */
    public function getWaitingMouvements(): array;
}
