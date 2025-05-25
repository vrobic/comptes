<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

class Map implements \Countable, \Iterator
{
    private ScalarObjectStorage|SplObjectStorage $data;

    public function __construct(
        private readonly string $keyType,
        private readonly string $valueType,
    ) {
        $this->data = ScalarObjectStorage::supportTypes($keyType) ? new ScalarObjectStorage() : new SplObjectStorage();
    }

    /**
     * @return static
     */
    public function add(mixed $key, mixed $value): self
    {
        if (!$this->data->isTypeOf($key, $this->keyType)) {
            throw new \InvalidArgumentException('type de la clef invalide');
        }

        if (!$value instanceof $this->valueType) {
            throw new \InvalidArgumentException('type de la valeur invalide');
        }

        $map = clone $this;
        $map->data->attach($key, $value);

        return $map;
    }

    /**
     * @return static
     */
    public function remove(mixed $key): self
    {
        if (!$this->data->isTypeOf($key, $this->keyType)) {
            throw new \InvalidArgumentException();
        }

        $map = clone $this;
        $map->data->detach($key);

        return $map;
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function get(mixed $key): mixed
    {
        return $this->data->offsetGet($key);
    }

    public function tryGet(mixed $key): mixed
    {
        try {
            return $this->data->offsetGet($key);
        } catch (\Exception) {
            return null;
        }
    }

    public function has(mixed $key): bool
    {
        return $this->data->offsetExists($key);
    }

    public function count(): int
    {
        return $this->data->count();
    }

    public function contains(mixed $value): bool
    {
        return $this->data->contains($value);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->data->count();
    }

    public function current(): mixed
    {
        return $this->data[$this->key()];
    }

    public function next(): void
    {
        $this->data->next();
    }

    public function key(): mixed
    {
        return $this->data->key();
    }

    public function valid(): bool
    {
        return $this->data->valid();
    }

    public function rewind(): void
    {
        $this->data->rewind();
    }

    public function __clone()
    {
        $this->data = clone $this->data;
    }

    public function getKeys(): array
    {
        $keys = [];
        foreach ($this as $key => $value) {
            $keys[] = $key;
        }

        return $keys;
    }

    public function toArray(
        callable $callableKey,
        callable $callableValue,
    ): array {
        $data = [];

        foreach ($this as $key => $item) {
            $data[$callableKey($key)] = $callableValue($item);
        }

        return $data;
    }

    public function filterByKey(callable $filter): static
    {
        $map = $this->clone();

        foreach ($this as $key => $value) {
            if (true === $filter($key)) {
                $map = $map->add($key, $value);
            }
        }

        return $map;
    }

    public function filterByValue(callable $filter): static
    {
        $map = $this->clone();

        foreach ($this as $key => $value) {
            if (true === $filter($value)) {
                $map = $map->add($key, $value);
            }
        }

        return $map;
    }

    public function reduce(callable $fn, mixed $carry): mixed
    {
        foreach ($this as $key => $item) {
            $carry = $fn($carry, $key, $item);
        }

        return $carry;
    }

    public function notIn(mixed ...$keys): self
    {
        // on utilise volontairement pas l'égalité stricte puisque ce sont des objets
        // et qu'on veut matcher si les valeurs dans l'objet sont les mêmes (comme pour un RAE)
        return $this->filterByKey(fn (mixed $key) => false === in_array($key, $keys));
    }

    public function chunk(int $size): \Generator
    {
        $i = 0;

        if (0 === $size) {
            throw new \RuntimeException();
        }

        $lastChunk = $this->clone();

        foreach ($this as $key => $value) {
            $lastChunk = $lastChunk->add($key, $value);

            if ((++$i) % $size === 0 || $i === count($this)) {
                yield $lastChunk;

                $lastChunk = $this->clone();
            }
        }
    }

    private function clone(): static
    {
        $clone = clone $this;
        $clone->data = ScalarObjectStorage::supportTypes($clone->keyType) ? new ScalarObjectStorage() : new SplObjectStorage();

        return $clone;
    }
}
