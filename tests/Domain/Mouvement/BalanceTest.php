<?php

declare(strict_types=1);

namespace App\Tests\Domain\Mouvement;

use App\Domain\Mouvement\Balance;
use PHPUnit\Framework\TestCase;

final class BalanceTest extends TestCase
{
    public function test_to_string(): void
    {
        self::assertSame(
            '+3 000,51 €',
            (string) new Balance(3000.51)
        );
    }

    public function test_nulle(): void
    {
        self::assertSame(
            0.,
            Balance::nulle()->montant
        );
    }

    public function test_est_positive(): void
    {
        self::assertTrue(new Balance(1.)->estPositive());
        self::assertFalse(Balance::nulle()->estPositive());
        self::assertFalse(new Balance(-1.)->estPositive());
    }

    public function test_est_négative(): void
    {
        self::assertTrue(new Balance(-1.)->estNégative());
        self::assertFalse(Balance::nulle()->estNégative());
        self::assertFalse(new Balance(1.)->estNégative());
    }

    public function test_additionner(): void
    {
        self::assertSame(
            5.,
            new Balance(3.)->additionner(new Balance(2.))->montant
        );
    }

    public function test_soustraire(): void
    {
        self::assertSame(
            1.,
            new Balance(3.)->soustraire(new Balance(2.))->montant
        );
    }

    public function test_diviser(): void
    {
        self::assertSame(
            1.5,
            new Balance(3.)->diviser(2.)->montant
        );
    }

    public function test_diviser_par_zéro(): void
    {
        self::expectException(\LogicException::class);

        new Balance(3.)->diviser(0);
    }
}
