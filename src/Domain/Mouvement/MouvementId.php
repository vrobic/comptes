<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Id\IdInterface;
use App\Infrastructure\Id\Uuid;

final readonly class MouvementId extends Uuid implements IdInterface
{
}
