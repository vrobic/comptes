<?php

declare(strict_types=1);

namespace App\Domain\DataStructure;

interface ObjectStorage extends \Countable, \Iterator, \ArrayAccess
{
    /**
     * @return bool
     */
    public function contains(object $object);

    public function offsetGet(mixed $object): mixed;

    /**
     * @return void
     */
    public function attach(object $object, mixed $data = null);

    public function isTypeOf(mixed $key, string $keyType): bool;

    public static function supportTypes(string $keyType): bool;
}
