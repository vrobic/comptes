<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure\Map;

use App\Domain\DataStructure\Map;

class ValueParKey extends Map
{
    public function __construct()
    {
        parent::__construct(Key::class, Value::class);
    }

    /** @param Key $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key->key;
    }
}
