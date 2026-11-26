<?php

declare(strict_types=1);

namespace App\Infrastructure\Temps;

use App\Domain\Temps\Date;
use App\Domain\Temps\HorlogeInterface;

/**
 * Lit l'heure de l'horloge système.
 */
final readonly class Horloge implements HorlogeInterface
{
    public function maintenant(): Date
    {
        return Date::createFromInterface(new \DateTimeImmutable());
    }
}
