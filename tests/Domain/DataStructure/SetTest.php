<?php

declare(strict_types=1);

namespace App\Tests\Domain\DataStructure;

use App\Domain\DataStructure\Set;
use App\Tests\Domain\DataStructure\Set\Value;
use App\Tests\Domain\DataStructure\Set\ValueCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Foo implements \Stringable
{
    public function __construct(public int $id = 1)
    {
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}

class Bar extends Foo
{
    public function __construct(public int $id = 2)
    {
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}

/**
 * @extends Set<Foo>
 */
class FooCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Foo::class);
    }

    public function contains(mixed $value): bool
    {
        foreach ($this as $el) {
            if ($el->id === $value->id) {
                return true;
            }
        }

        return false;
    }

    public function getUniqueKey(mixed $value): ?string
    {
        return (string) $value->id;
    }
}

class SetTest extends TestCase
{
    public function test_construct(): void
    {
        $set = new Set(Foo::class);

        self::assertInstanceOf(Set::class, $set);
        self::assertEmpty($set);

        // Sous-classe
        $set = new FooCollection();
        self::assertInstanceOf(FooCollection::class, $set);
        self::assertEmpty($set);
    }

    public function test_clone(): void
    {
        $set = new Set(Foo::class)
            ->add($foo1 = new Foo(1), $foo2 = new Foo(2));

        $clone = clone $set;

        self::assertNotSame($set, $clone);
        self::assertCount(2, $clone);
        self::assertTrue($clone->contains($foo1));
        self::assertTrue($clone->contains($foo2));

        // Modification du clone ne doit pas affecter l'original
        $clone = $clone->add(new Foo(3));
        self::assertCount(2, $set);
        self::assertCount(3, $clone);
    }

    public function test_get_type(): void
    {
        $set = new Set(Foo::class);
        self::assertSame(Foo::class, $set->getType());

        $set = new FooCollection();
        self::assertSame(Foo::class, $set->getType());
    }

    public function test_add(): void
    {
        $set1 = new Set(Foo::class);
        $set2 = $set1->add(new Foo(), new Foo());
        $set3 = $set2->add(new Foo());

        self::assertInstanceOf(Set::class, $set2);
        self::assertCount(2, $set2);
        self::assertCount(3, $set3);

        // Vérification immuabilité
        self::assertNotSame($set1, $set2);
        self::assertEmpty($set1);
    }

    public function test_add_doublons_possibles_sans_unique_key(): void
    {
        // Même objet (= même référence)
        $foo = new Foo(1);
        $set = new Set(Foo::class);
        $set = $set->add($foo);
        $set = $set->add($foo, $foo);
        self::assertCount(1, $set);
        self::assertTrue($set->contains($foo));

        // Même valeur mais objets différents
        $set = $set->add(new Foo(1));
        self::assertCount(2, $set);
        self::assertTrue($set->contains($foo));
        self::assertFalse($set->contains(new Foo(1)));
    }

    public function test_add_doublons_impossibles_avec_unique_key(): void
    {
        // Même objet (= même référence)
        $value = new Value('a');
        $set = new ValueCollection();
        $set = $set->add($value);
        $set = $set->add($value, $value);
        self::assertCount(1, $set);
        self::assertTrue($set->contains($value));

        // Même valeur mais objets différents
        $set = $set->add(new Value('a'));
        self::assertCount(1, $set);
        self::assertTrue($set->contains($value));
        self::assertTrue($set->contains(new Value('a')));
    }

    public function test_add_type_invalide(): void
    {
        $set = new Set(Foo::class);

        $set = $set->add(new \stdClass());

        self::assertNotEmpty($set);
    }

    public function test_remove(): void
    {
        $set1 = new FooCollection()
            ->add($foo1 = new Foo(4))
            ->add($foo2 = new Foo(5));

        $set2 = $set1->remove($foo1, $foo2);

        self::assertInstanceOf(Set::class, $set2);
        self::assertInstanceOf(FooCollection::class, $set2);
        self::assertEmpty($set2);

        // Vérification immuabilité
        self::assertNotSame($set1, $set2);
        self::assertCount(2, $set1);

        // Suppression d'une valeur inexistante ne doit pas lever d'erreur
        $foo1 = new Foo(1);
        $foo2 = new Foo(2);
        $fooInexistant = new Foo(99);

        $set = new Set(Foo::class)->add($foo1, $foo2);
        $result = $set->remove($fooInexistant);

        self::assertCount(2, $result);
        self::assertTrue($result->contains($foo1));
        self::assertTrue($result->contains($foo2));

        // Suppression avec uniqueKey
        $set = new ValueCollection()
            ->add(new Value('a'), new Value('b'), new Value('c'));
        $result = $set->remove(new Value('b'));

        self::assertCount(2, $result);
        self::assertTrue($result->contains(new Value('a')));
        self::assertFalse($result->contains(new Value('b')));
        self::assertTrue($result->contains(new Value('c')));
    }

    public function test_remove_type_invalide(): void
    {
        $set = new Set(Foo::class);

        self::assertEmpty($set);

        $set = $set->remove(new \stdClass());

        self::assertEmpty($set);
    }

    public function test_count(): void
    {
        $set = new Set(Foo::class);
        self::assertSame(0, $set->count());

        $set = $set->add(new Foo());
        self::assertSame(1, $set->count());

        $set = $set->add(new Foo());
        self::assertSame(2, $set->count());

        $set = $set->add(new Foo());
        self::assertSame(3, $set->count());
    }

    public function test_is_empty(): void
    {
        $set = new Set(Foo::class);
        self::assertTrue($set->isEmpty());

        $set = $set->add(new Foo());
        self::assertFalse($set->isEmpty());
    }

    public function test_contains(): void
    {
        $value = new Foo();

        $set = new Set(Foo::class);
        self::assertFalse($set->contains($value));

        $set = $set->add($value);
        self::assertTrue($set->contains($value));
    }

    public function test_contains_type_invalide(): void
    {
        $set = new Set(Foo::class);

        self::assertFalse($set->contains(new \stdClass()));
    }

    public function test_find_first(): void
    {
        $set = new Set(Foo::class);
        $set = $set->add(new Foo(4));
        $set = $set->add($expected = new Foo(1));
        $set = $set->add(new Foo(2));
        $set = $set->add(new Foo(1));

        $actual = $set->findFirst(static fn(Foo $element): bool => $element->id === 1);
        self::assertInstanceOf(Foo::class, $actual);
        self::assertSame($expected, $actual);

        $actual = $set->findFirst(static fn(Foo $element): bool => $element->id === 0);
        self::assertNull($actual);
    }

    public function test_has_one(): void
    {
        $set = new Set(Foo::class);
        $set = $set->add(new Foo(4));
        $set = $set->add(new Foo(1));
        $set = $set->add(new Foo(2));

        self::assertTrue($set->hasOne(static fn(Foo $element): bool => $element->id === 1));

        self::assertTrue($set->hasOne(static fn(Foo $element): bool => $element->id === 2));

        self::assertFalse($set->hasOne(static fn(Foo $element): bool => $element->id === 0));
    }

    public function test_filter(): void
    {
        $set = new Set(Foo::class);
        $set = $set->add(new Foo());
        $set = $set->add(new Foo());
        $set = $set->add($value = new Foo());

        $filteredSet = $set->filter(
            static fn(Foo $element): bool => $element === $value
        );

        self::assertInstanceOf(Set::class, $filteredSet);
        self::assertSame(1, $filteredSet->count());
        self::assertTrue($filteredSet->contains($value));

        // Vérification immuabilité
        self::assertNotSame($set, $filteredSet);
        self::assertCount(3, $set);

        // Aucun résultat
        $filteredSet = $set->filter(static fn(Foo $element): bool => $element->id > 100);

        self::assertEmpty($filteredSet);
        self::assertInstanceOf(Set::class, $filteredSet);
    }

    public function test_reduce(): void
    {
        $set = new Set(Foo::class)
            ->add(new Foo(1))
            ->add(new Foo(2))
            ->add(new Foo(3));

        self::assertSame(16, $set->reduce(function(int $carry, Foo $foo) {
            $carry += $foo->id;

            return $carry;
        }, 10));

        // Set vide
        $emptySet = new Set(Foo::class);
        $result = $emptySet->reduce(fn(int $carry, Foo $foo) => $carry + $foo->id, 0);
        self::assertSame(0, $result);
    }

    public function test_map(): void
    {
        $set = new FooCollection()
            ->add(new Foo(1))
            ->add(new Foo(2))
            ->add(new Foo(3));

        $fn = fn(Foo $foo): int => $foo->id;

        $mappedSet = $set->map(fn(Foo $foo): Foo => new Foo($foo->id + 1));

        self::assertInstanceOf(FooCollection::class, $mappedSet);
        self::assertSame([2, 3, 4], $mappedSet->toArray($fn));

        // Vérification immuabilité
        self::assertNotSame($set, $mappedSet);
        self::assertSame([1, 2, 3], $set->toArray($fn));

        // Avec doublons : tous les éléments sont mappés sur la même valeur
        $mappedSet = $set->map(fn(Foo $foo): Foo => new Foo(1));
        self::assertCount(1, $mappedSet);
        self::assertSame([1], $mappedSet->toArray($fn));
    }

    public function test_map_type_invalide(): void
    {
        self::expectExceptionObject(new \ErrorException('Warning: Attempt to read property "id" on int'));

        $set = new FooCollection()
            ->add(new Foo(1))
            ->add(new Foo(2))
            ->add(new Foo(3));

        $set->map(fn(Foo $foo): int => $foo->id);
    }

    public function test_to_array(): void
    {
        $set = new Set(Foo::class)
            ->add($f1 = new Foo(1), $f2 = new Foo(2), $f3 = new Foo(3));

        $expected = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        $fn = fn(Foo $foo): array => ['id' => $foo->id];

        self::assertSame($expected, $set->toArray($fn));
        self::assertSame([$f1, $f2, $f3], $set->toArray());

        // Set vide
        $emptySet = new Set(Foo::class);
        self::assertSame([], $emptySet->toArray());
        self::assertSame([], $emptySet->toArray(fn(Foo $foo): int => $foo->id));
    }

    public function test_to_json(): void
    {
        $set = new FooCollection()->add(
            new Foo(1),
            new Foo(2),
            new Foo(3),
            new Foo(4),
            new Foo(5),
        );

        self::assertSame("[1,2,3,4,5]", $set->toJson(
            static fn(Foo $foo): int => $foo->id,
        ));
        self::assertSame('[{"id":1},{"id":2},{"id":3},{"id":4},{"id":5}]', $set->toJson());

        // Set vide
        $emptySet = new Set(Foo::class);
        self::assertSame('[]', $emptySet->toJson());
    }

    public function test_sort(): void
    {
        $set = new FooCollection();
        $set = $set->add($value1 = new Foo(4));
        $set = $set->add($value2 = new Foo(1));
        $set = $set->add($value3 = new Foo(2));

        $sortedSet = $set->sort(
            static fn(Foo $element1, Foo $element2): int => $element1->id <=> $element2->id
        );

        self::assertInstanceOf(Set::class, $sortedSet);
        self::assertInstanceOf(FooCollection::class, $sortedSet);
        self::assertEquals([$value2, $value3, $value1], iterator_to_array($sortedSet));

        // Vérification immuabilité
        self::assertNotSame($set, $sortedSet);
        self::assertEquals([$value1, $value2, $value3], iterator_to_array($set));
    }

    public function test_iteration(): void
    {
        $set = new Set(Foo::class);
        $set = $set->add($value1 = new Foo());
        $set = $set->add($value2 = new Foo());
        $set = $set->add($value3 = new Foo());

        $expected = [
            $value1,
            $value2,
            $value3,
        ];

        self::assertSame($expected, iterator_to_array($set));
    }

    public function test_first(): void
    {
        $set = new Set(Foo::class);
        $set = $set->add($expected = new Foo(3));
        $set = $set->add(new Foo(2));
        $set = $set->add(new Foo(1));

        $actual = $set->first();
        self::assertInstanceOf(Foo::class, $actual);
        self::assertSame($expected, $actual);
    }

    public function test_first_set_vide(): void
    {
        self::expectException(\UnderflowException::class);

        self::assertFalse(new Set(Foo::class)->first());
    }

    public function test_current(): void
    {
        $set = new Set(Foo::class)
            ->add($foo1 = new Foo(1), $foo2 = new Foo(2));

        self::assertSame($foo1, $set->current());

        $set->next();
        self::assertSame($foo2, $set->current());
    }

    public function test_current_set_vide(): void
    {
        self::expectException(\OutOfRangeException::class);

        self::assertFalse(new Set(Foo::class)->current());
    }

    public function test_key(): void
    {
        $set = new Set(Foo::class)
            ->add(new Foo(1), new Foo(2), new Foo(3));

        self::assertSame(0, $set->key());

        $set->next();
        self::assertSame(1, $set->key());

        $set->next();
        self::assertSame(2, $set->key());
    }

    public function test_valid(): void
    {
        $set = new Set(Foo::class)
            ->add(new Foo(1), new Foo(2));

        self::assertTrue($set->valid());

        $set->next();
        self::assertTrue($set->valid());

        $set->next();
        self::assertFalse($set->valid());
    }

    public function test_rewind(): void
    {
        $set = new Set(Foo::class)
            ->add($foo1 = new Foo(1), new Foo(2), new Foo(3));

        $set->next();
        $set->next();
        self::assertSame(2, $set->key());

        $set->rewind();
        self::assertSame(0, $set->key());
        self::assertSame($foo1, $set->current());
    }

    public function test_merge(): void
    {
        $set = new FooCollection()
            ->add(new Foo(1))
            ->add(new Foo(2));

        $set2 = new FooCollection()->add(new Foo(3));

        $result = $set->merge($set2);

        $fn = fn(Foo $foo): int => $foo->id;

        self::assertCount(3, $result);
        self::assertInstanceOf(FooCollection::class, $result);

        // Vérification immuabilité
        self::assertNotSame($set, $result);
        self::assertNotSame($set2, $result);
        self::assertSame([1, 2], $set->toArray($fn));
        self::assertSame([3], $set2->toArray($fn));

        // Avec doublons : l'élément avec id=2 ne doit pas être dupliqué
        $set3 = new FooCollection()
            ->add(new Foo(2))
            ->add(new Foo(4));

        $result = $set->merge($set3);
        self::assertCount(3, $result);
        self::assertSame([1, 2, 4], $result->toArray($fn));
    }

    public function test_intersect(): void
    {
        $set1 = new FooCollection();
        $set2 = new FooCollection();
        $set3 = new FooCollection();
        $set4 = new FooCollection();
        $set1 = $set1
            ->add(new Foo(1))
            ->add(new Foo(0))
            ->add(new Foo(3));

        $set2 = $set2
            ->add(new Foo(1))
            ->add(new Foo(5))
            ->add(new Foo(10))
            ->add(new Foo(99))
            ->add(new Foo(3));

        $set4 = $set4
            ->add(new Foo(111))
            ->add(new Foo(222));

        $intersect12 = $set1->intersect($set2);
        $intersect13 = $set1->intersect($set3);
        $intersect23 = $set2->intersect($set3);
        $intersect14 = $set1->intersect($set4);

        $toArray = fn(Foo $el) => $el->id;

        // Vérification immuabilité
        self::assertNotSame($set1, $intersect12);
        self::assertNotSame($set2, $intersect12);
        self::assertSame([1, 0, 3], $set1->toArray($toArray));
        self::assertSame([1, 5, 10, 99, 3], $set2->toArray($toArray));

        self::assertSame([1, 3], $intersect12->toArray($toArray));
        self::assertSame([], $intersect14->toArray($toArray));
        self::assertSame([], $intersect13->toArray($toArray));
        self::assertSame([], $intersect23->toArray($toArray));
    }

    public function test_intersect_types_differents(): void
    {
        $fooCollection = new FooCollection()->add(new Foo(1));
        $set = new Set(Foo::class)->add(new Foo(1));

        $this->expectException(\RuntimeException::class);

        $fooCollection->intersect($set);
    }

    public function test_vider(): void
    {
        $set = new FooCollection()
            ->add(new Foo(1), new Foo(2), new Foo(3));

        $emptySet = $set->vider();

        self::assertEmpty($emptySet);
        self::assertInstanceOf(FooCollection::class, $emptySet);

        // Vérification immuabilité
        self::assertNotSame($set, $emptySet);
        self::assertCount(3, $set);
    }

    public function test_prepend(): void
    {
        $set1 = new Set(Foo::class);
        $set2 = $set1->prepend(new Bar());

        self::assertInstanceOf(Set::class, $set2);
        self::assertEquals(new Bar(), $set2->current());

        // Vérification immuabilité
        self::assertNotSame($set1, $set2);
        self::assertEmpty($set1);

        $set1 = new FooCollection()->add(new Foo(2), new Foo(3));
        $set2 = $set1->prepend(new Foo(1));

        $fn = fn(Foo $foo): int => $foo->id;

        self::assertSame([1, 2, 3], $set2->toArray($fn));

        // Vérification immuabilité
        self::assertNotSame($set1, $set2);
        self::assertSame([2, 3], $set1->toArray($fn));

        // Élément existant : ne doit pas être dupliqué ni déplacé
        $set = new FooCollection()->add(new Foo(1), new Foo(2), new Foo(3));
        $result = $set->prepend(new Foo(2));
        self::assertCount(3, $result);
        self::assertSame([1, 2, 3], $result->toArray($fn));
    }

    public function test_prepend_type_invalide(): void
    {
        $set = new Set(Foo::class);

        $set = $set->prepend(new \stdClass());

        self::assertNotEmpty($set);
    }

    #[DataProvider('chunks')]
    public function test_chunk(int $setSize, int $chunksSize, array $counts): void
    {
        $set = new FooCollection();

        for ($i=0; $i < $setSize; $i++) {
            $set = $set->add(new Foo($i));
        }

        $originalCount = $set->count();

        $chunked = $set->chunk($chunksSize);

        // Vérification immuabilité
        self::assertCount($originalCount, $set);

        self::assertCount(count($counts), $chunked);

        foreach ($chunked as $k => $chunk) {
            self::assertNotSame($set, $chunk);
            self::assertCount($counts[$k], $chunk);
        }
    }

    public static function chunks(): array
    {
        return [
            [150, 100, [100, 50]],
            [11, 5, [5, 5, 1]],
            [100, 500, [100]],
            [0, 5, []],
            [1, 100, [1]],
            [13, 3, [3, 3, 3, 3, 1]],
            [4, 1, [1, 1, 1, 1]],
            [4, 0, [1, 1, 1, 1]],
        ];
    }

    public function test_slice(): void
    {
        $set = new FooCollection()->add(
            new Foo(1),
            new Foo(2),
            new Foo(3),
            new Foo(4),
            new Foo(5),
        );

        $fn = static fn(Foo $el): int => $el->id;

        $sliced = $set->slice(0, 1);
        self::assertCount(1, $sliced);
        self::assertSame(1, $sliced->first()->id);

        // Vérification immuabilité
        self::assertNotSame($set, $sliced);
        self::assertSame([1, 2, 3, 4, 5], $set->toArray($fn));

        $sliced = $set->slice(1, 3);
        self::assertCount(3, $sliced);
        self::assertSame([2, 3, 4], $sliced->toArray($fn));

        $sliced = $set->slice(4, 3);
        self::assertCount(1, $sliced);
        self::assertSame(5, $sliced->first()->id);

        $sliced = $set->slice(10, 5);
        self::assertEmpty($sliced);

        $sliced = $set->slice(0, 0);
        self::assertEmpty($sliced);

        $sliced = $set->slice(0, -3);
        self::assertEmpty($sliced);

        $sliced = $set->slice(2, null);
        self::assertSame([3, 4, 5], $sliced->toArray($fn));

        $sliced = $set->slice(4, null);
        self::assertSame(5, $sliced->first()->id);

        $sliced = $set->slice(5, null);
        self::assertEmpty($sliced);

        // Offset négatif (prend les éléments depuis la fin)
        $sliced = $set->slice(-2, null);
        self::assertSame([4, 5], $sliced->toArray($fn));

        $sliced = $set->slice(-3, 2);
        self::assertSame([3, 4], $sliced->toArray($fn));
    }
}
