<?php

declare(strict_types=1);

namespace App\Domain\Categorie;

use App\Domain\Id\IdInterface;
use App\Infrastructure\Id\Uuid;

final readonly class CategorieId extends Uuid implements IdInterface
{
}
