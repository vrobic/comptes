<?php

declare(strict_types=1);

namespace App\Domain\Compte;

use App\Domain\DataStructure\Set;

final class CompteCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Compte::class);
    }

    /** @param Compte $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->id;
    }
}
