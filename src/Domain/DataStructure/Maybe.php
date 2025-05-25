<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

/** @template T */
final readonly class Maybe
{
    /** @param ?T $valeur */
    public function __construct(
        private mixed $valeur,
        public bool $estDéfini,
    ) {
    }

    /**
     * @param ?T $valeur
     *
     * @return self<T>
     */
    public static function from(mixed $valeur): self
    {
        return new self($valeur, true);
    }

    public static function nothing(): self
    {
        return new self(null, false);
    }

    /**
     * @return ?T
     */
    public function getValeur(): mixed
    {
        if (false === $this->estDéfini) {
            throw new \RuntimeException('Aucune valeur définie.');
        }

        return $this->valeur;
    }
}
