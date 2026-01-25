<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compte;

use App\Domain\Compte\Solde;
use PHPUnit\Framework\TestCase;

final class SoldeTest extends TestCase
{
    public function test_to_string(): void
    {
        self::assertSame(
            '3 000,51 €',
            (string) new Solde(3000.51)
        );
    }

    public function test_nul(): void
    {
        self::assertSame(
            0.,
            Solde::nul()->montant
        );
    }

    public function test_est_nul(): void
    {
        self::assertTrue(Solde::nul()->estNul());
        self::assertFalse(new Solde(3.)->estNul());
        self::assertFalse(new Solde(-3.)->estNul());
    }

    public function test_additionner(): void
    {
        self::assertSame(
            5.,
            new Solde(3.)->additionner(new Solde(2.))->montant
        );
    }
}
