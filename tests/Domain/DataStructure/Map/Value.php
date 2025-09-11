<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure\Map;

class Value
{
    public function __construct(public ?string $value = null)
    {
    }
}
