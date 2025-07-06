<?php

declare(strict_types=1);

namespace App\Infrastructure\Id;

use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Id\IdInterface;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UuidGenerator implements IdGeneratorInterface
{
    public function générer(): IdInterface
    {
        return new Uuid((string) SymfonyUuid::v4());
    }
}
