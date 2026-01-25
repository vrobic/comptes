<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure\Set;

use App\Domain\DataStructure\Set;

/**
 * @extends Set<Value>
 */
final class ValueCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Value::class);
    }

    /** @param Value $value */
    public function getUniqueKey(mixed $value): string
    {
        return (string) $value->value;
    }
}
