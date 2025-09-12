<?php

namespace App\Tests\Domain\Temps;

use App\Domain\Temps\Annee;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
use PHPUnit\Framework\TestCase;

final class PeriodeTest extends TestCase
{
    public function test_constructeur_dates_cohérentes(): void
    {
        self::assertInstanceOf(
            Periode::class,
            new Periode(
                new \DateTimeImmutable('2025-08-21'),
                new \DateTimeImmutable('2025-08-21'),
            )
        );
    }

    public function test_constructeur_dates_incohérentes(): void
    {
        self::expectException(\LogicException::class);

        new Periode(
            new \DateTimeImmutable('2025-08-21'),
            new \DateTimeImmutable('2025-08-20'),
        );
    }

    public function test_étendre(): void
    {
        $périodeA = new Periode(
            $début = new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2025-03-01'),
        );
        $périodeB = new Periode(
            new \DateTimeImmutable('2025-02-01'),
            $fin = new \DateTimeImmutable('2025-07-01'),
        );

        $périodeÉtendue = $périodeA->étendre($périodeB);

        self::assertSame($début, $périodeÉtendue->début);
        self::assertSame($fin, $périodeÉtendue->fin);

        $périodeÉtendue = $périodeB->étendre($périodeA);

        self::assertSame($début, $périodeÉtendue->début);
        self::assertSame($fin, $périodeÉtendue->fin);
    }

    public function test_années(): void
    {
        $période = new Periode(
            new \DateTimeImmutable('2025-08-21'),
            new \DateTimeImmutable('2027-01-01'),
        );

        self::assertEquals(
            [
                new Annee(2025),
                new Annee(2026),
                new Annee(2027),
            ],
            [...$période->années()]
        );
    }

    public function test_mois(): void
    {
        $période = new Periode(
            new \DateTimeImmutable('2025-08-21'),
            new \DateTimeImmutable('2026-03-01'),
        );

        self::assertEquals(
            [
                new Mois(2025, 8),
                new Mois(2025, 9),
                new Mois(2025, 10),
                new Mois(2025, 11),
                new Mois(2025, 12),
                new Mois(2026, 1),
                new Mois(2026, 2),
                new Mois(2026, 3),
            ],
            [...$période->mois()]
        );
    }
}
