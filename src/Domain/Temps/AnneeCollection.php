<?php

declare(strict_types=1);

namespace App\Domain\Temps;

use App\Domain\DataStructure\Set;

/**
 * @extends Set<Annee>
 */
final class AnneeCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Annee::class);
    }
}
