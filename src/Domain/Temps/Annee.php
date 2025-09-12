<?php

declare(strict_types=1);

namespace App\Domain\Temps;

final readonly class Annee
{
    public function __construct(public int $année)
    {
    }

    public static function fromDate(\DateTimeImmutable $date): self
    {
        return new self((int) $date->format('Y'));
    }

    public function __toString(): string
    {
        return (string) $this->année;
    }
}
