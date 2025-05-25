<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

interface Denormalizer
{
    public function denormalize(array $data): mixed;
}
