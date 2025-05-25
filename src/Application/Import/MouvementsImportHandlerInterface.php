<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Mouvement\Mouvement;

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
