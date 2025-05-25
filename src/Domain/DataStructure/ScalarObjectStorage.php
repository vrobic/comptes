<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

final class ScalarObjectStorage implements ObjectStorage
{
    private array $storage = [];

    public function contains(object $object): bool
    {
        return false !== array_search($object, $this->storage);
    }

    public function count(): int
    {
        return \count($this->storage);
    }

    public function rewind(): void
    {
        reset($this->storage);
    }

    public function current(): mixed
    {
        return current($this->storage);
    }

    public function next(): void
    {
        next($this->storage);
    }

    public function key(): mixed
    {
        return key($this->storage);
    }

    public function offsetGet(mixed $object): mixed
    {
        if (!$this->offsetExists($object)) {
            throw new \UnexpectedValueException();
        }

        return $this->storage[$object];
    }

    public function valid(): bool
    {
        return false !== $this->current();
    }

    public function attach(mixed $key, mixed $data = null): void
    {
        $this->storage[$key] = $data;
    }

    public function isTypeOf(mixed $key, string $keyType): bool
    {
        return match ($keyType) {
            'string' => is_string($key),
            'int' => is_int($key),
            default => throw new \UnexpectedValueException('invalid key type'),
        };
    }

    public static function supportTypes(string $keyType): bool
    {
        return \in_array($keyType, ['string', 'int']);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->storage[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->storage[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->storage[$offset]);
    }
}
