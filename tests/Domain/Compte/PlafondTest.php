<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compte;

use App\Domain\Compte\Plafond;
use PHPUnit\Framework\TestCase;

final class PlafondTest extends TestCase
{
    public function test_to_string(): void
    {
        self::assertSame(
            '3 000,51 €',
            (string) new Plafond(3000.51)
        );
    }

    public function test_est_positif(): void
    {
        self::assertTrue(new Plafond(1.)->estPositif());
        self::assertFalse(new Plafond(0.)->estPositif());
        self::assertFalse(new Plafond(-1.)->estPositif());
    }
}
