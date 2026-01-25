<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

use Ds\Set as BaseSet;

/**
 * Comme pour le Map, on pourrait implémenter \IteratorAggrege,
 * ce qui éviterait d'avoir à redéfinir current, next, key, valid et rewind.
 *
 * @template T of object
 *
 * @implements \Iterator<int, T>
 */
class Set implements \Iterator, \Countable
{
    /**
     * Si le Set surcharge la méthode getUniqueKey pour retourner une clé textuelle unique de chaque élément,
     * cette propriété sera utilisée pour que la méthode contains base sa comparaison
     * sur cette clé (même clé textuelle), plutôt que sur une égalité stricte (même référence mémoire).
     *
     * C'est très utile pour les value objects
     * qu'on considère égaux par les valeurs qu'ils contiennent,
     * plutôt que par leur référence mémoire.
     *
     * @var array<string, true>
     */
    private array $uniqueKeys = [];
    /** @var BaseSet<T> */
    private BaseSet $data;
    private int $position = 0;

    public function __construct(private readonly string $type)
    {
        $this->data = new BaseSet();
    }

    public function __clone(): void
    {
        $this->data = $this->data->copy();
        // uniqueKeys est un array, il est copié par valeur automatiquement
        $this->position = 0;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** @param T ...$values */
    public function add(mixed ...$values): static
    {
        $set = clone $this;

        foreach ($values as $value) {
            if ($set->contains($value)) {
                continue;
            }

            $set->data->add($value);

            $uniqueKey = $this->getUniqueKey($value);
            if (is_string($uniqueKey)) {
                $set->uniqueKeys[$uniqueKey] = true;
            }
        }

        return $set;
    }

    /** @param T ...$values */
    public function remove(mixed ...$values): static
    {
        $set = clone $this;

        foreach ($values as $value) {
            if (!$set->contains($value)) {
                continue;
            }

            $uniqueKey = $this->getUniqueKey($value);

            // Si on utilise les clés uniques, on doit trouver l'élément par sa clé
            if (is_string($uniqueKey)) {
                // Trouver et retirer l'élément correspondant à cette clé unique
                foreach ($set->data as $existingValue) {
                    if ($this->getUniqueKey($existingValue) === $uniqueKey) {
                        $set->data->remove($existingValue);
                        break;
                    }
                }

                unset($set->uniqueKeys[$uniqueKey]);
            } else {
                $set->data->remove($value);
            }
        }

        return $set;
    }

    public function count(): int
    {
        return $this->data->count();
    }

    public function isEmpty(): bool
    {
        return $this->data->isEmpty();
    }

    /** @param T $value */
    public function contains(mixed $value): bool
    {
        $uniqueKey = $this->getUniqueKey($value);

        return is_string($uniqueKey) ?
            isset($this->uniqueKeys[$uniqueKey]) :
            $this->data->contains($value);
    }

    /** @return ?T */
    public function findFirst(callable $fn): mixed
    {
        foreach ($this->data as $value) {
            if ($fn($value)) {
                return $value;
            }
        }

        return null;
    }

    public function hasOne(callable $fn): bool
    {
        foreach ($this->data as $value) {
            if ($fn($value)) {
                return true;
            }
        }

        return false;
    }

    public function filter(callable $fn): static
    {
        return $this->createSubset($this->data->filter($fn)->toArray());
    }

    public function reduce(callable $fn, mixed $initial): mixed
    {
        return $this->data->reduce($fn, $initial);
    }

    public function map(callable $fn): static
    {
        /** @var static $set */
        $set = new static($this->type);
        $seenKeys = [];

        foreach ($this->data as $value) {
            $newValue = $fn($value);
            $uniqueKey = $this->getUniqueKey($newValue);

            // Déduplication via uniqueKey si disponible, sinon via contains()
            if (is_string($uniqueKey)) {
                if (isset($seenKeys[$uniqueKey])) {
                    continue;
                }
                $seenKeys[$uniqueKey] = true;
            } elseif ($set->contains($newValue)) {
                continue;
            }

            $set->data->add($newValue);
        }

        $set->uniqueKeys = $seenKeys;

        return $set;
    }

    public function toArray(?callable $fn = null): array
    {
        if (null === $fn) {
            return $this->data->toArray();
        }

        return array_map($fn, $this->data->toArray());
    }

    /**
     * @throws \JsonException
     */
    public function toJson(?callable $fn = null): string
    {
        return json_encode($this->toArray($fn), JSON_THROW_ON_ERROR);
    }

    public function sort(callable $fn): static
    {
        $array = $this->data->toArray();
        usort($array, $fn);

        /** @var static $set */
        $set = new static($this->type);
        $set->data = new BaseSet($array);
        $set->uniqueKeys = $this->uniqueKeys;

        return $set;
    }

    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this->data->get($this->position);
    }

    public function next(): void
    {
        ++$this->position;
    }

    /** @return int */
    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->position < $this->data->count();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return T
     */
    public function first(): mixed
    {
        return $this->data->first();
    }

    /**
     * @return T
     */
    public function last(): mixed
    {
        return $this->data->last();
    }

    /** @param static $set2 */
    public function merge(self $set2): static
    {
        $set = clone $this;

        /** @var T $value */
        foreach ($set2 as $value) {
            if ($set->contains($value)) {
                continue;
            }

            $set->data->add($value);

            $uniqueKey = $this->getUniqueKey($value);
            if (is_string($uniqueKey)) {
                $set->uniqueKeys[$uniqueKey] = true;
            }
        }

        return $set;
    }

    public function vider(): static
    {
        /** @var static $set */
        $set = new static($this->type);
        $set->data = new BaseSet();
        $set->uniqueKeys = [];

        return $set;
    }

    /**
     * @param static $set2
     *                     Retourne un nouveau Set avec les élements communs
     *                     aux deux Sets selon la méthode contains()
     *
     * @throws \RuntimeException
     */
    public function intersect(self $set2): static
    {
        if ($this::class !== $set2::class) {
            throw new \RuntimeException();
        }

        $intersectData = [];

        foreach ($this->data as $value) {
            if ($set2->contains($value)) {
                $intersectData[] = $value;
            }
        }

        return $this->createSubset($intersectData);
    }

    /** @param T $value */
    public function prepend(mixed $value): static
    {
        if ($this->contains($value)) {
            return clone $this;
        }

        /** @var static $set */
        $set = new static($this->type);
        $newData = array_merge([$value], $this->data->toArray());
        $set->data = new BaseSet($newData);
        $set->uniqueKeys = $this->uniqueKeys;

        $uniqueKey = $this->getUniqueKey($value);
        if (is_string($uniqueKey)) {
            $set->uniqueKeys[$uniqueKey] = true;
        }

        return $set;
    }

    /** @return static[] */
    public function chunk(int $size): array
    {
        if ($size < 1) {
            $size = 1;
        }

        $chunks = [];
        $currentData = [];

        foreach ($this->data as $value) {
            $currentData[] = $value;

            if (count($currentData) === $size) {
                $chunks[] = $this->createSubset($currentData);
                $currentData = [];
            }
        }

        if (!empty($currentData)) {
            $chunks[] = $this->createSubset($currentData);
        }

        return $chunks;
    }

    public function slice(int $offset, ?int $length = null): static
    {
        // Si length est 0 ou négatif, retourner un set vide
        if (null !== $length && $length <= 0) {
            return $this->createSubset([]);
        }

        $sliced = $this->data->slice($offset, $length)->toArray();

        return $this->createSubset($sliced);
    }

    /** @param T $value */
    public function getUniqueKey(mixed $value): ?string
    {
        return null;
    }

    /**
     * Crée un nouveau Set du même type à partir d'un tableau de valeurs
     * déjà présentes dans le Set d'origine, donc déjà validées.
     *
     * En n'utilisant pas la méthode add, on évite
     * la vérification des doublons, un clone, et l'ajout des éléments un par un.
     *
     * L'objectif de cette méthode est d'optimiser les performances du Set, en prenant quelques raccourcis.
     *
     * @param T[] $values
     */
    private function createSubset(array $values): static
    {
        /** @var static $set */
        $set = new static($this->type);
        $set->data = new BaseSet($values);

        if (!empty($this->uniqueKeys)) {
            $set->uniqueKeys = $this->buildUniqueKeys($values);
        }

        return $set;
    }

    /**
     * Reconstruit le tableau des clés uniques à partir des données,
     * pour les Set qui utilisent des clés uniques.
     *
     * @param T[] $values
     *
     * @return array<string, true>
     */
    private function buildUniqueKeys(array $values): array
    {
        $keys = [];

        foreach ($values as $value) {
            $uniqueKey = $this->getUniqueKey($value);

            if (is_string($uniqueKey)) {
                $keys[$uniqueKey] = true;
            }
        }

        return $keys;
    }
}
