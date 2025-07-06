<?php

declare(strict_types=1);

namespace App\Domain\Id;

interface IdInterface
{
    public function __toString(): string;

    public static function estValide(string $valeur): bool;

    public function estÉgalÀ(self $id): bool;
}
