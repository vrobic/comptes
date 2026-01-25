<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure\Set;

class Value
{
    public function __construct(public ?string $value = null) {}

    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
