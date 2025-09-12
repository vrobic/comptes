<?php

declare(strict_types=1);

namespace App\Tests\Domain\Temps;

use App\Domain\Temps\Mois;
use PHPUnit\Framework\TestCase;

final class MoisTest extends TestCase
{
    public function test_constructeur_valide(): void
    {
        self::assertInstanceOf(
            Mois::class,
            new Mois(2019, 01)
        );
    }

    public function test_constructeur_invalide_mois_inférieur_à_un(): void
    {
        self::expectException(\LogicException::class);

        new Mois(2019, 0);
    }

    public function test_constructeur_invalide_mois_supérieur_à_douze(): void
    {
        self::expectException(\LogicException::class);

        new Mois(2019, 13);
    }

    public function test_from_date(): void
    {
        self::assertEquals(
            new Mois(2011, 10),
            Mois::fromDate(new \DateTimeImmutable('2011-10-25'))
        );
    }

    public function test_to_string(): void
    {
        self::assertSame('août 2025', (string) new Mois(2025, 8));
    }

    public function test_début(): void
    {
        self::assertEquals(
            new \DateTimeImmutable('2024-10-01')->setTime(0, 0, 0, 0),
            new Mois(2024, 10)->début()
        );
    }

    public function test_fin(): void
    {
        self::assertEquals(
            new \DateTimeImmutable('2028-02-29')->setTime(23, 59, 59, 999999),
            new Mois(2028, 2)->fin()
        );
    }
}
