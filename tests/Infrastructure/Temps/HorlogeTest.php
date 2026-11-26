<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Temps;

use App\Infrastructure\Temps\Horloge;
use PHPUnit\Framework\TestCase;

final class HorlogeTest extends TestCase
{
    public function test_maintenant(): void
    {
        $avant = new \DateTimeImmutable();
        $maintenant = new Horloge()->maintenant();
        $après = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($avant, $maintenant);
        self::assertLessThanOrEqual($après, $maintenant);
    }
}
