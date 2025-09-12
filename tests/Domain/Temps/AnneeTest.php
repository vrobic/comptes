<?php

declare(strict_types=1);

namespace App\Tests\Domain\Temps;

use App\Domain\Temps\Annee;
use PHPUnit\Framework\TestCase;

final class AnneeTest extends TestCase
{
    public function test_from_date(): void
    {
        self::assertEquals(
            new Annee(2011),
            Annee::fromDate(new \DateTimeImmutable('2011-10-25'))
        );
    }

    public function test_to_string(): void
    {
        self::assertSame('2025', (string) new Annee(2025));
    }
}
