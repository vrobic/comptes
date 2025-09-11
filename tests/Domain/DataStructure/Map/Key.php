<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure\Map;

class Key
{
    public function __construct(public ?string $key = null)
    {
    }
}
