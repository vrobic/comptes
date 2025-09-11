<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure;

use App\Domain\DataStructure\Map;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\Tests\Domain\DataStructure\Map\EnumNative;
use App\Tests\Domain\DataStructure\Map\Key;
use App\Tests\Domain\DataStructure\Map\Value;
use App\Tests\Domain\DataStructure\Map\ValueParKey;

class MapTest extends TestCase
{
    #[DataProvider('constructeurClésValides')]
    #[DataProvider('constructeurValeursValides')]
    public function test_constructeur_valide(Map $map): void
    {
        self::assertInstanceOf(Map::class, $map);
        self::assertEmpty($map);
    }

    public function test_clone(): void
    {
        $map1 = new ValueParKey()
            ->add($clé1 = new Key('a'), new Value('OPERA'));

        $map2 = clone $map1;

        self::assertEquals(
            $map1->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value
            ),
            $map2->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value
            )
        );

        $map2->add($clé2 = new Key('b'), new Value('ENERGIE'));

        // Vérifie que l'original n'est pas modifié
        self::assertCount(1, $map1);
        self::assertTrue($map1->has($clé1));
        self::assertFalse($map1->has($clé2));
    }

    #[DataProvider('couplesClésInvalides')]
    public function test_add_clé_invalide(Map $map, mixed $clé): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la clé invalide'));

        $map->add($clé, new Value());
    }

    #[DataProvider('couplesValeursInvalides')]
    public function test_add_valeur_invalide(Map $map, mixed $valeur): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la valeur invalide'));

        $map->add(new Key(), $valeur);
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_add_clé_valide(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map
            ->add($clé1, new Value())
            ->add($clé2, new Value());

        self::assertSame([$clé1, $clé2], $map->getKeys());
    }

    #[DataProvider('couplesValeursObjetValides')]
    #[DataProvider('couplesValeursEnumValides')]
    #[DataProvider('couplesValeursScalairesValides')]
    public function test_add_valeur_valide(Map $map, mixed $valeur1, mixed $valeur2): void
    {
        $clé1 = new Key('a');
        $clé2 = new Key('b');

        $map = $map
            ->add($clé1, $valeur1)
            ->add($clé2, $valeur2);

        self::assertSame($valeur1, $map->get($clé1));
        self::assertSame($valeur2, $map->get($clé2));
    }

    public function test_add_immuabilité(): void
    {
        $map1 = new ValueParKey();
        $map2 = $map1->add(new Key(), new Value());

        self::assertNotSame($map1, $map2);
        self::assertInstanceOf(Map::class, $map2);
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_remove(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map
            ->add($clé1, new Value())
            ->add($clé2, new Value());

        self::assertCount(2, $map);
        self::assertSame([$clé1, $clé2], $map->getKeys());

        $map = $map->remove($clé1);

        self::assertCount(1, $map);
        self::assertSame([$clé2], $map->getKeys());

        $map = $map->remove($clé2);

        self::assertEmpty($map);
    }

    #[DataProvider('couplesValeursInvalides')]
    public function test_remove_clé_invalide(Map $map, mixed $clé): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la clé invalide'));

        $map->remove($clé, new Value());
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_remove_clé_inexistante(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map->add($clé1, new Value());

        self::assertCount(1, $map);
        self::assertTrue($map->has($clé1));

        $map = $map->remove($clé2);

        self::assertCount(1, $map);
        self::assertTrue($map->has($clé1));
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_get(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map
            ->add($clé1, $valeur1 = new Value())
            ->add($clé2, $valeur2 = new Value());

        self::assertSame($valeur1, $map->get($clé1));
        self::assertSame($valeur2, $map->get($clé2));
    }

    #[DataProvider('couplesValeursInvalides')]
    public function test_get_clé_invalide(Map $map, mixed $clé): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la clé invalide'));

        $map->get($clé, new Value());
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_get_clé_inexistante(Map $map, mixed $clé1, mixed $clé2): void
    {
        self::expectExceptionObject(new \UnexpectedValueException('clé introuvable'));

        $map
            ->add($clé1, new Value())
            ->get($clé2);
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_try_get(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map
            ->add($clé1, $valeur1 = new Value())
            ->add($clé2, $valeur2 = new Value());

        self::assertSame($valeur1, $map->tryGet($clé1));
        self::assertSame($valeur2, $map->tryGet($clé2));
    }

    #[DataProvider('couplesValeursInvalides')]
    public function test_try_get_clé_invalide(Map $map, mixed $clé): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la clé invalide'));

        $map->tryGet($clé, new Value());
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_try_get_clé_inexistante(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map->add($clé1, new Value());

        self::assertNull($map->tryGet($clé2));
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_has(Map $map, mixed $clé1, mixed $clé2): void
    {
        $map = $map->add($clé1, new Value());

        self::assertTrue($map->has($clé1));
        self::assertFalse($map->has($clé2));
    }

    #[DataProvider('couplesValeursInvalides')]
    public function test_has_clé_invalide(Map $map, mixed $clé): void
    {
        self::expectExceptionObject(new \InvalidArgumentException('type de la clé invalide'));

        $map->has($clé);
    }

    public function test_count(): void
    {
        $map = new ValueParKey();
        self::assertSame(0, $map->count());

        $map = $map->add(new Key('a'), new Value());
        self::assertSame(1, $map->count());

        $map = $map->add(new Key('b'), new Value());
        self::assertSame(2, $map->count());

        $map = $map->add(new Key('c'), new Value());
        self::assertSame(3, $map->count());
    }

    public function test_is_empty(): void
    {
        $map = new ValueParKey();
        self::assertTrue($map->isEmpty());

        $map = $map->add(new Key(), new Value());
        self::assertFalse($map->isEmpty());
    }

    #[DataProvider('couplesClésObjetValides')]
    #[DataProvider('couplesClésEnumValides')]
    #[DataProvider('couplesClésScalairesValides')]
    public function test_get_keys(Map $map, mixed $clé1, mixed $clé2): void
    {
        self::assertSame([], $map->getKeys());

        $map = $map
            ->add($clé1, new Value())
            ->add($clé2, new Value());

        self::assertSame([$clé1, $clé2], $map->getKeys());
    }

    public function test_to_array(): void
    {
        $map = new ValueParKey();

        self::assertSame(
            [],
            $map->toArray(
                static fn (Key $cléClass): string => $cléClass->key,
                static fn (Value $cléClass): string => $cléClass->value,
            )
        );

        $map = $map
            ->add(new Key('foo'), new Value('OPERA'))
            ->add(new Key('bar'), new Value('ENERGIE'))
            ->add(new Key('baz'), new Value('LYON'));

        self::assertSame(
            [
                'foo' => 'OPERA',
                'bar' => 'ENERGIE',
                'baz' => 'LYON',
            ],
            $map->toArray(
                static fn (Key $cléClass): string => $cléClass->key,
                static fn (Value $cléClass): string => $cléClass->value,
            )
        );
    }

    public function test_filter_by_key(): void
    {
        $map = new ValueParKey()
            ->add(new Key('foo'), new Value('OPERA'))
            ->add(new Key('bar'), new Value('ENERGIE'))
            ->add(new Key('baz'), new Value('LYON'));

        $mapfiltrée1 = $map->filterByKey(
            static fn (Key $clé) => 'foo' === $clé->key
        );

        $mapfiltrée2 = $map->filterByKey(
            static fn (Key $clé) => strlen((string) $clé->key) > 1
        );

        $mapfiltrée3 = $map->filterByKey(
            static fn (Key $clé) => 'JeanPierrePapin' === $clé->key
        );

        $mapfiltrée4 = $map->filterByKey(
            static fn (Key $clé) => $clé->key
        );

        self::assertSame(
            [
                'foo' => 'OPERA',
            ],
            $mapfiltrée1->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value,
            )
        );

        self::assertSame(
            [
                'foo' => 'OPERA',
                'bar' => 'ENERGIE',
                'baz' => 'LYON',
            ],
            $mapfiltrée2->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value,
            )
        );

        self::assertEmpty($mapfiltrée3);

        // Pour s'assurer que le callable doit renvoyer true et non truthy
        self::assertEmpty($mapfiltrée4);

        // On s'assure de l'immuabilité
        self::assertCount(3, $map);
    }

    public function test_filter_by_value(): void
    {
        $map = new ValueParKey()
            ->add(new Key('foo'), $valeur1 = new Value('OPERA'))
            ->add(new Key('bar'), $valeur1)
            ->add(new Key('baz'), $valeur3 = new Value('LYON'));

        $mapFiltrée1 = $map->filterByValue(static fn (Value $valeur) => $valeur === $valeur1);
        $mapFiltrée2 = $map->filterByValue(static fn (Value $valeur) => $valeur === $valeur3);
        $mapFiltréeVide = $map->filterByValue(static fn (Value $valeur) => $valeur);

        self::assertSame(
            [
                'foo' => 'OPERA',
                'bar' => 'OPERA',
            ],
            $mapFiltrée1->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value,
            )
        );

        self::assertSame(
            [
                'baz' => 'LYON',
            ],
            $mapFiltrée2->toArray(
                static fn (Key $clé): string => $clé->key,
                static fn (Value $valeur): string => $valeur->value,
            )
        );

        // Pour s'assurer que le callable doit renvoyer true et non truthy
        self::assertEmpty($mapFiltréeVide);

        // On s'assure de l'immuabilité
        self::assertCount(3, $map);
    }

    public function test_reduce(): void
    {
        $callback = static fn (array $carry, EnumNative $clé, Value $item) => [...$carry, "{$clé->name}_{$item->value}"];

        $map = new class extends Map {
            public function __construct()
            {
                parent::__construct(EnumNative::class, Value::class);
            }

            /** @param EnumNative $key */
            public function getUniqueKey(mixed $key): string
            {
                return $key->name;
            }
        };

        self::assertEmpty($map->reduce($callback, []));

        $map = $map
            ->add(EnumNative::BLEU, new Value('OPERA'))
            ->add(EnumNative::ROUGE, new Value('ENERGIE'));

        self::assertSame(
            [
                'BLEU_OPERA',
                'ROUGE_ENERGIE',
            ],
            $map->reduce($callback, [])
        );
    }

    public function test_chunk(): void
    {
        $map = new class extends Map {
            public function __construct()
            {
                parent::__construct(EnumNative::class, Value::class);
            }

            /** @param EnumNative $key */
            public function getUniqueKey(mixed $key): string
            {
                return $key->name;
            }
        };

        $map = $map
            ->add(EnumNative::BLEU, new Value())
            ->add(EnumNative::ROUGE, new Value())
            ->add(EnumNative::CARMIN, new Value())
            ->add(EnumNative::BLEU_CERISE, new Value())
            ->add(EnumNative::BLOUGE, new Value())
            ->add(EnumNative::PIPI, new Value())
            ->add(EnumNative::CACA_D_OIE, new Value());

        $chunks = $map->chunk(3);

        $arr = iterator_to_array($chunks);

        self::assertCount(3, $arr);

        self::assertCount(3, $arr[0]);
        self::assertCount(3, $arr[1]);
        self::assertCount(1, $arr[2]);
    }

    #[DataProvider('chunkSizesInvalides')]
    public function test_chunk_invalide(int $size): void
    {
        $this->expectException(\RuntimeException::class);

        $map = new class extends Map {
            public function __construct()
            {
                parent::__construct(EnumNative::class, Value::class);
            }

            /** @param EnumNative $key */
            public function getUniqueKey(mixed $key): string
            {
                return $key->name;
            }
        };

        $map = $map
            ->add(EnumNative::BLEU, new Value())
            ->add(EnumNative::CACA_D_OIE, new Value());

        $map->chunk($size)->current();
    }

    public function test_itération(): void
    {
        $map = new ValueParKey()
            ->add($clé1 = new Key('a'), $valeur1 = new Value())
            ->add($clé2 = new Key('b'), $valeur2 = new Value())
            ->add($clé3 = new Key('c'), $valeur3 = new Value());

        $expected = [
            ['clé' => $clé1, 'valeur' => $valeur1],
            ['clé' => $clé2, 'valeur' => $valeur2],
            ['clé' => $clé3, 'valeur' => $valeur3],
        ];

        $i = 0;
        foreach ($map as $clé => $valeur) {
            self::assertSame($expected[$i]['clé'], $clé);
            self::assertSame($expected[$i]['valeur'], $valeur);

            ++$i;
        }
    }

    public function test_first_value(): void
    {
        $map = new ValueParKey();

        self::assertNull($map->firstValue());

        $map = $map
            ->add(new Key('a'), $expected = new Value())
            ->add(new Key('b'), new Value())
            ->add(new Key('c'), new Value());

        self::assertSame($expected, $map->firstValue());
    }

    /**
     * Des maps ayant tous les mêmes types de valeurs, mais différents types de clés.
     */
    public static function constructeurClésValides(): \Generator
    {
        yield 'clé objet' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\stdClass::class, Value::class);
                }

                /** @param \stdClass $key */
                public function getUniqueKey(mixed $key): string
                {
                    return spl_object_hash($key);
                }
            },
        ];
        yield 'clé objet bis' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\DateTimeImmutable::class, Value::class);
                }

                /** @param \DateTimeImmutable $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->format('Ymd');
                }
            },
        ];
        yield 'clé objet ter' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\Exception::class, Value::class);
                }

                /** @param \Exception */
                public function getUniqueKey(mixed $key): string
                {
                    return $key::class.$key->getMessage();
                }
            },
        ];
        yield 'clé objet quater' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, Value::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
        ];
        yield 'clé enum native' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(EnumNative::class, Value::class);
                }

                /** @param EnumNative $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->name;
                }
            },
        ];
        yield 'clé booléen' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('bool', Value::class);
                }

                /** @param bool $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key ? 'vrai' : 'faux';
                }
            },
        ];
        yield 'clé nombre entier' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('int', Value::class);
                }

                /** @param int $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
        ];
        yield 'clé nombre flottant' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('float', Value::class);
                }

                /** @param float $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
        ];
        yield 'clé chaîne de caractères' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('string', Value::class);
                }

                /** @param string $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key;
                }
            },
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de clés, mais différents types de valeurs.
     */
    public static function constructeurValeursValides(): \Generator
    {
        yield 'valeur objet' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, \stdClass::class);
                }

                /** @param \stdClass $key */
                public function getUniqueKey(mixed $key): string
                {
                    return spl_object_hash($key);
                }
            },
        ];
        yield 'valeur objet bis' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, \DateTimeImmutable::class);
                }

                /** @param \DateTimeImmutable $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->format('Ymd');
                }
            },
        ];
        yield 'valeur objet ter' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, \Exception::class);
                }

                /** @param \Exception */
                public function getUniqueKey(mixed $key): string
                {
                    return $key::class.$key->getMessage();
                }
            },
        ];
        yield 'valeur objet quater' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, Key::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
        ];
        yield 'valeur enum native' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, EnumNative::class);
                }

                /** @param EnumNative $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->name;
                }
            },
        ];
        yield 'valeur booléen' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, 'bool');
                }

                /** @param bool $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key ? 'vrai' : 'faux';
                }
            },
        ];
        yield 'valeur nombre entier' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, 'int');
                }

                /** @param int $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
        ];
        yield 'valeur nombre flottant' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, 'float');
                }

                /** @param float $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
        ];
        yield 'valeur chaîne de caractères' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Value::class, 'string');
                }

                /** @param string $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key;
                }
            },
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de valeurs, mais différents types de clés.
     */
    public static function couplesClésInvalides(): \Generator
    {
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\DateTimeImmutable::class, Value::class);
                }

                /** @param \DateTimeImmutable $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->format('Ymd');
                }
            },
            new \stdClass(),
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('bool', Value::class);
                }

                /** @param bool $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key ? 'vrai' : 'faux';
                }
            },
            1,
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('float', Value::class);
                }

                /** @param float $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
            new \stdClass(),
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('string', Value::class);
                }

                /** @param string $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key;
                }
            },
            1,
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de clés, mais différents types de valeurs.
     */
    public static function couplesValeursInvalides(): \Generator
    {
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, \DateTimeImmutable::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new \stdClass(),
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'bool');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            1,
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'float');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new \stdClass(),
        ];
        yield [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'string');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            1,
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de valeurs, mais différents types de clés.
     */
    public static function couplesClésObjetValides(): \Generator
    {
        yield 'objet' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\stdClass::class, Value::class);
                }

                /** @param \stdClass $key */
                public function getUniqueKey(mixed $key): string
                {
                    return spl_object_hash($key);
                }
            },
            new \stdClass(),
            new \stdClass(),
        ];
        yield 'objet bis' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\DateTimeImmutable::class, Value::class);
                }

                /** @param \DateTimeImmutable $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->format('Ymd');
                }
            },
            new \DateTimeImmutable('2025-08-21'),
            new \DateTimeImmutable('2025-08-25'),
        ];
        yield 'objet ter' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(\Exception::class, Value::class);
                }

                /** @param \Exception $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key::class.$key->getMessage();
                }
            },
            new \Exception('cassé'),
            new \Exception('détruit'),
        ];
        yield 'objet quater' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, Value::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new Key('a'),
            new Key('b'),
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de clés, mais différents types de valeurs.
     */
    public static function couplesValeursObjetValides(): \Generator
    {
        yield 'objet' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, \stdClass::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new \stdClass(),
            new \stdClass(),
        ];
        yield 'objet bis' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, \DateTimeImmutable::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new \DateTimeImmutable('2025-08-21'),
            new \DateTimeImmutable('2025-08-25'),
        ];
        yield 'objet ter' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, \Exception::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new \Exception('cassé'),
            new \Exception('détruit'),
        ];
        yield 'objet quater' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, Key::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            new Key('a'),
            new Key('b'),
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de valeurs, mais différents types de clés.
     */
    public static function couplesClésEnumValides(): \Generator
    {
        yield 'enum native' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(EnumNative::class, Value::class);
                }

                /** @param EnumNative $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key->name;
                }
            },
            EnumNative::BLEU,
            EnumNative::ROUGE,
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de clés, mais différents types de valeurs.
     */
    public static function couplesValeursEnumValides(): \Generator
    {
        yield 'enum native' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, EnumNative::class);
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            EnumNative::BLEU,
            EnumNative::ROUGE,
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de valeurs, mais différents types de clés.
     */
    public static function couplesClésScalairesValides(): \Generator
    {
        yield 'booléen' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('bool', Value::class);
                }

                /** @param bool $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key ? 'vrai' : 'faux';
                }
            },
            true,
            false,
        ];
        yield 'nombre entier' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('int', Value::class);
                }

                /** @param int $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
            1,
            2,
        ];
        yield 'nombre flottant' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('float', Value::class);
                }

                /** @param float $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key;
                }
            },
            1.,
            2.,
        ];
        yield 'chaîne de caractères' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct('string', Value::class);
                }

                /** @param string $key */
                public function getUniqueKey(mixed $key): string
                {
                    return $key;
                }
            },
            'brutus',
            'liar',
        ];
    }

    /**
     * Des maps ayant tous les mêmes types de clés, mais différents types de valeurs.
     */
    public static function couplesValeursScalairesValides(): \Generator
    {
        yield 'booléen' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'bool');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            true,
            false,
        ];
        yield 'nombre entier' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'int');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            1,
            2,
        ];
        yield 'nombre flottant' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'float');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            1.,
            2.,
        ];
        yield 'chaîne de caractères' => [
            new class extends Map {
                public function __construct()
                {
                    parent::__construct(Key::class, 'string');
                }

                /** @param Key $key */
                public function getUniqueKey(mixed $key): string
                {
                    return (string) $key->key;
                }
            },
            'brutus',
            'liar',
        ];
    }

    public static function chunkSizesInvalides(): \Generator
    {
        yield 'négative' => [-1];
        yield 'nulle' => [0];
    }
}
