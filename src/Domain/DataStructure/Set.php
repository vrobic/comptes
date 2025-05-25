<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

/** @phpstan-consistent-constructor */
class Set implements \Iterator, \Countable
{
    private array $uniqueKeys = [];
    private array $data = [];

    public function __construct(private readonly string $type)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function add(mixed ...$values): static
    {
        $set = clone $this;

        foreach ($values as $value) {
            if (!$value instanceof $this->type) {
                throw new \InvalidArgumentException();
            }

            if ($set->contains($value)) {
                continue;
            }

            $set->data[] = $value;
            $set->uniqueKeys[$this->getUniqueKey($value)] = true;
        }

        return $set;
    }

    public static function from(mixed ...$values): static
    {
        return (new static())->add(...$values);
    }

    public function remove(mixed ...$values): static
    {
        $set = clone $this;

        foreach ($values as $value) {
            if (!$value instanceof $this->type) {
                throw new \InvalidArgumentException();
            }

            if (!$this->contains($value)) {
                continue;
            }

            unset($set->data[array_search($value, $this->data)]);
            unset($set->uniqueKeys[$this->getUniqueKey($value)]);
        }

        // Reindexer les clefs
        $set->data = array_values($set->data);

        return $set;
    }

    public function count(): int
    {
        return \count($this->data);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function contains(mixed $value): bool
    {
        if (!$value instanceof $this->type) {
            throw new \InvalidArgumentException();
        }

        return isset($this->uniqueKeys[$this->getUniqueKey($value)]);
    }

    public function findFirst(callable $f): mixed
    {
        foreach ($this->data as $value) {
            if ($f($value)) {
                return $value;
            }
        }

        return null;
    }

    public function hasOne(callable $f): bool
    {
        foreach ($this->data as $value) {
            if ($f($value)) {
                return true;
            }
        }

        return false;
    }

    public function filter(callable $f): static
    {
        $data = array_filter($this->data, $f);

        $set = $this->vider();

        return $set->add(...$data);
    }

    public function reduce(callable $f, mixed $initial): mixed
    {
        return array_reduce($this->data, $f, $initial);
    }

    public function map(callable $f): static
    {
        $data = array_map($f, $this->data);

        $set = $this->vider();

        return $set->add(...$data);
    }

    public function toArray(?callable $fn = null): array
    {
        if (null === $fn) {
            return $this->data;
        }

        return array_map($fn, $this->data);
    }

    /**
     * @throws \JsonException
     */
    public function toJson(?callable $fn = null): string
    {
        return json_encode($this->toArray($fn), JSON_THROW_ON_ERROR);
    }

    public function sort(callable $f): static
    {
        $data = $this->data;

        usort($data, $f);

        $set = clone $this;
        $set->data = $data;

        return $set;
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function key(): mixed
    {
        return key($this->data);
    }

    public function valid(): bool
    {
        return null !== $this->key();
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function first(): mixed
    {
        return reset($this->data);
    }

    public function last(): mixed
    {
        return end($this->data);
    }

    public function merge(self $collection): static
    {
        $self = clone $this;

        foreach ($collection as $item) {
            $self = $self->add($item);
        }

        return $self;
    }

    public function vider(): static
    {
        $set = clone $this;
        $set->data = [];
        $set->uniqueKeys = [];

        return $set;
    }

    /**
     * @param static $set2
     *                     Retourne un nouveau Set avec les élements communs
     *                     aux deux Sets selon la méthode contains()
     */
    public function intersect(self $set2): static
    {
        if ($this::class !== $set2::class) {
            throw new \RuntimeException();
        }

        $set = $this->vider();

        foreach ($this as $el) {
            if ($set2->contains($el)) {
                $set = $set->add($el);
            }
        }

        return $set;
    }

    public function implode(string $delimiter = ','): string
    {
        try {
            (string) $this->first();
        } catch (\Error $e) {
            throw new \InvalidArgumentException(previous: $e);
        }

        return implode($delimiter, $this->data);
    }

    public function prepend(mixed $value): static
    {
        if (!$value instanceof $this->type) {
            throw new \InvalidArgumentException();
        }

        $set = clone $this;

        if ($this->contains($value)) {
            return $set;
        }

        array_unshift($set->data, $value);
        $set->uniqueKeys[$this->getUniqueKey($value)] = true;

        return $set;
    }

    /** @return static[] */
    public function chunk(int $size): array
    {
        if ($size < 1) {
            $size = 1;
        }

        $chunks = [];
        $currentSet = $this->vider();

        $i = 0;
        foreach ($this as $object) {
            ++$i;

            $currentSet = $currentSet->add($object);

            if ($i === $size) {
                $i = 0;
                $chunks[] = $currentSet;
                $currentSet = $this->vider();
            }
        }

        if (false === $currentSet->isEmpty()) {
            $chunks[] = $currentSet;
        }

        return $chunks;
    }

    public function slice(int $offset, ?int $length = null): static
    {
        $set = clone $this;
        $data = $set->data;
        $dataLength = count($set->data);

        $set = $set->vider();

        if (null === $length) {
            $length = $dataLength - $offset;
        }

        $dataToAdd = [];
        for ($i = $offset; $i < min($dataLength, $length + $offset); ++$i) {
            $dataToAdd[] = $data[$i];
        }

        return $set->add(...$dataToAdd);
    }

    protected function getUniqueKey(mixed $value): string
    {
        return spl_object_hash($value);
    }
}
