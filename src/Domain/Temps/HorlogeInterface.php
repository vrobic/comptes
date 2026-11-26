<?php

declare(strict_types=1);

namespace App\Domain\Temps;

interface HorlogeInterface
{
    /**
     * La date et l'heure courantes.
     */
    public function maintenant(): Date;
}
