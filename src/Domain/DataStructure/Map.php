<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

use Ds\Map as BaseMap;

/**
 * Ce map fonctionne par valeur, via la méthode getUniqueKey.
 */
abstract class Map implements \Countable, \IteratorAggregate
{
    private BaseMap $data; // @see https://www.php.net/manual/en/class.ds-map.php

    // Cache pour éviter les validations répétitives
    private static bool $keyValidationCache = false;
    private static bool $valueValidationCache = false;
    private static mixed $lastValidatedKey = null;
    private static mixed $lastValidatedValue = null;

    /** @throws \InvalidArgumentException */
    public function __construct(
        public readonly string $keyType,
        public readonly string $valueType,
    ) {
        if (
            !class_exists($keyType)
            && !interface_exists($keyType)
            && !in_array($keyType, ['string', 'int', 'float', 'bool'], true)
        ) {
            throw new \InvalidArgumentException("$keyType n'est pas un type de clé supporté");
        }

        if (
            !class_exists($valueType)
            && !interface_exists($valueType)
            && !in_array($valueType, ['string', 'int', 'float', 'bool'], true)
        ) {
            throw new \InvalidArgumentException("$valueType n'est pas un type de valeur supporté");
        }

        $this->data = new BaseMap();
    }

    public function __clone()
    {
        $this->data = clone $this->data;

        // Reset du cache
        self::$keyValidationCache = false;
        self::$valueValidationCache = false;
        self::$lastValidatedKey = null;
        self::$lastValidatedValue = null;
    }

    abstract public function getUniqueKey(mixed $key): string;

    /**
     * Parce qu'elle fait un clone, cette méthode est coûteuse si appelée en boucle.
     *
     * Ce n'est pas mon combat d'aujourd'hui, mais on pourrait imaginer une méthode supplémentaire
     * pour gérer l'ajout massif d'éléments, en ne faisant qu'un seul clone pour l'ensemble,
     * à l'instar des méthodes de filtre. Une IA saurait proposer une implémentation.
     *
     * @throws \InvalidArgumentException
     */
    public function add(mixed $key, mixed $value): static
    {
        if (!$this->isValidKeyCached($key)) {
            throw new \InvalidArgumentException('type de la clé invalide');
        }

        if (!$this->isValidValueCached($value)) {
            throw new \InvalidArgumentException('type de la valeur invalide');
        }

        $uniqueKey = $this->getUniqueKey($key);

        if ($this->data->hasKey($uniqueKey)) {
            throw new \InvalidArgumentException('clé déjà présente');
        }

        $map = clone $this;
        $map->data->put($uniqueKey, [$key, $value]);

        return $map;
    }

    /**
     * Parce qu'elle fait un clone, cette méthode est coûteuse si appelée en boucle.
     *
     * Ce n'est pas mon combat d'aujourd'hui, mais on pourrait imaginer une méthode supplémentaire
     * pour gérer l'ajout massif d'éléments, en ne faisant qu'un seul clone pour l'ensemble,
     * à l'instar des méthodes de filtre. Une IA saurait proposer une implémentation.
     *
     * @throws \InvalidArgumentException
     */
    public function remove(mixed $key): static
    {
        if (!$this->isValidKeyCached($key)) {
            throw new \InvalidArgumentException('type de la clé invalide');
        }

        $uniqueKey = $this->getUniqueKey($key);

        // Backward compatibility
        if (!$this->data->hasKey($uniqueKey)) {
            return $this;
        }

        $map = clone $this;
        $map->data->remove($uniqueKey);

        return $map;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function get(mixed $key): mixed
    {
        if (!$this->isValidKeyCached($key)) {
            throw new \InvalidArgumentException('type de la clé invalide');
        }

        $uniqueKey = $this->getUniqueKey($key);

        // Backward compatibility
        if (!$this->data->hasKey($uniqueKey)) {
            throw new \UnexpectedValueException('clé introuvable');
        }

        return $this->data->get($uniqueKey)[1];
    }

    /** @throws \InvalidArgumentException */
    public function tryGet(mixed $key): mixed
    {
        if (!$this->isValidKeyCached($key)) {
            throw new \InvalidArgumentException('type de la clé invalide');
        }

        $uniqueKey = $this->getUniqueKey($key);

        return $this->data->hasKey($uniqueKey) ? $this->data->get($uniqueKey)[1] : null;
    }

    /** @throws \InvalidArgumentException */
    public function has(mixed $key): bool
    {
        if (!$this->isValidKeyCached($key)) {
            throw new \InvalidArgumentException('type de la clé invalide');
        }

        return $this->data->hasKey($this->getUniqueKey($key));
    }

    public function count(): int
    {
        return $this->data->count();
    }

    public function isEmpty(): bool
    {
        return $this->data->isEmpty();
    }

    public function getKeys(): array
    {
        $keys = [];

        foreach ($this->data->values() as [$key, $value]) {
            $keys[] = $key;
        }

        return $keys;
    }

    public function toArray(
        callable $callableKey,
        callable $callableValue,
    ): array {
        $data = [];

        foreach ($this->data->values() as [$key, $value]) {
            $data[$callableKey($key)] = $callableValue($value);
        }

        return $data;
    }

    public function filterByKey(callable $filter): static
    {
        $data = new BaseMap();

        foreach ($this->data as $uniqueKey => [$key, $value]) {
            if (true === $filter($key)) {
                $data->put($uniqueKey, [$key, $value]);
            }
        }

        return self::fromRaw($this->keyType, $this->valueType, $data);
    }

    public function filterByValue(callable $filter): static
    {
        $data = new BaseMap();

        foreach ($this->data as $uniqueKey => [$key, $value]) {
            if (true === $filter($value)) {
                $data->put($uniqueKey, [$key, $value]);
            }
        }

        return self::fromRaw($this->keyType, $this->valueType, $data);
    }

    public function reduce(callable $fn, mixed $carry): mixed
    {
        foreach ($this->data->values() as [$key, $value]) {
            $carry = $fn($carry, $key, $value);
        }

        return $carry;
    }

    /** @throws \RuntimeException */
    public function chunk(int $size): \Generator
    {
        if ($size <= 0) {
            throw new \RuntimeException("la taille d'un chunk doit être strictement positive");
        }

        $data = new BaseMap();
        $i = 0;

        foreach ($this->data as $uniqueKey => $item) {
            $data->put($uniqueKey, $item);
            ++$i;

            if (0 === $i % $size) {
                yield self::fromRaw($this->keyType, $this->valueType, $data);
                $data = new BaseMap();
            }
        }

        if ($data->count() > 0) {
            yield self::fromRaw($this->keyType, $this->valueType, $data);
        }
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->data->values() as [$key, $value]) {
            yield $key => $value; // expose les clés d'origine
        }
    }

    public function firstValue(): mixed
    {
        foreach ($this->data->values() as [$key, $value]) {
            return $value;
        }

        return null;
    }

    /**
     * Permet d'optimiser les performances dans les méthodes qui bouclent (filter, chunk),
     * en évitant de faire un add (et donc un clone coûteux) à chaque itération.
     */
    private static function fromRaw(
        string $keyType,
        string $valueType,
        BaseMap $data,
    ): static {
        $map = new static($keyType, $valueType);
        $map->data = $data;

        return $map;
    }

    /**
     * Validation de clé avec cache pour éviter les répétitions.
     */
    private function isValidKeyCached(mixed $key): bool
    {
        if (self::$lastValidatedKey === $key && self::$keyValidationCache) {
            return true;
        }

        $isValid = $this->isValidType($key, $this->keyType);
        self::$lastValidatedKey = $key;
        self::$keyValidationCache = $isValid;

        return $isValid;
    }

    /**
     * Validation de valeur avec cache pour éviter les répétitions.
     */
    private function isValidValueCached(mixed $value): bool
    {
        if (self::$lastValidatedValue === $value && self::$valueValidationCache) {
            return true;
        }

        $isValid = $this->isValidType($value, $this->valueType);
        self::$lastValidatedValue = $value;
        self::$valueValidationCache = $isValid;

        return $isValid;
    }

    private function isValidType(mixed $item, string $expectedType): bool
    {
        // Cache des types de classes/interfaces
        static $classCache = [];
        static $interfaceCache = [];

        // Objets et interfaces
        if (!isset($classCache[$expectedType])) {
            $classCache[$expectedType] = class_exists($expectedType);
        }
        if (!isset($interfaceCache[$expectedType])) {
            $interfaceCache[$expectedType] = interface_exists($expectedType);
        }

        if ($classCache[$expectedType] || $interfaceCache[$expectedType]) {
            return $item instanceof $expectedType;
        }

        $actualType = gettype($item);
        $normalizedType = [
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
        ][$actualType] ?? $actualType;

        return $normalizedType === $expectedType;
    }
}
