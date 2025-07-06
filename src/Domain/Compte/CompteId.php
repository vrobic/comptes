<?php

declare(strict_types=1);

namespace App\Domain\Compte;

use App\Domain\Id\IdInterface;
use App\Infrastructure\Id\Uuid;

final readonly class CompteId extends Uuid implements IdInterface
{
}
