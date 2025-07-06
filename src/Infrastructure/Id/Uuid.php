<?php

declare(strict_types=1);

namespace App\Infrastructure\Id;

use App\Domain\Id\IdInterface;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

readonly class Uuid implements IdInterface
{
    public function __construct(private string $valeur)
    {
        if (!self::estValide($this->valeur)) {
            throw new \InvalidArgumentException(sprintf("La classe %s n'autorise pas la valeur %s.", self::class, $this->valeur));
        }
    }

    public function __toString(): string
    {
        return $this->valeur;
    }

    public static function estValide(string $valeur): bool
    {
        return SymfonyUuid::isValid($valeur);
    }

    public function estÉgalÀ(IdInterface $id): bool
    {
        return $id::class === $this::class && (string) $id === (string) $this;
    }
}
