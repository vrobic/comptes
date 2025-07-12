<?php

declare(strict_types=1);

namespace App\Domain\Temps;

final readonly class Periode
{
    public function __construct(
        public \DateTimeImmutable $début,
        public \DateTimeImmutable $fin,
    ) {
        if ($début > $fin) {
            throw new \LogicException();
        }
    }
}
