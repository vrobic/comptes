<?php

declare(strict_types=1);

namespace App\Domain\Id;

interface IdGeneratorInterface
{
    public function générer(): IdInterface;
}
