<?php

declare(strict_types=1);

namespace App\Domain\Temps;

use App\Domain\DataStructure\Set;

final class MoisCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Mois::class);
    }
}
