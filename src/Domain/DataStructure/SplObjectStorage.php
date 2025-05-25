<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

use SplObjectStorage as NatifSplObjectStorage;

final class SplObjectStorage extends NatifSplObjectStorage implements ObjectStorage
{
    public function isTypeOf(mixed $key, string $keyType): bool
    {
        return $key instanceof $keyType;
    }

    public static function supportTypes(string $keyType): bool
    {
        return NatifSplObjectStorage::class === $keyType;
    }

    #[\ReturnTypeWillChange]
    public function key(): mixed
    {
        return $this->current();
    }
}
