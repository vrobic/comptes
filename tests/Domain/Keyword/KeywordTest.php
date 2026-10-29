<?php

declare(strict_types=1);

namespace App\Tests\Domain\Keyword;

use App\Tests\GenererInstance;
use PHPUnit\Framework\TestCase;

final class KeywordTest extends TestCase
{
    public function test_to_string(): void
    {
        self::assertSame('DECATHLON', (string) GenererInstance::keyword(word: 'DECATHLON'));
    }
}
