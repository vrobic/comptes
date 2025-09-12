<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\BalanceAnnuelle;
use App\Domain\Mouvement\Balance;
use App\Domain\Temps\Annee;
use PHPUnit\Framework\TestCase;

final class BalanceAnnuelleTest extends TestCase
{
    public function test_trier_par_date(): void
    {
        $année2024 = new Annee(2024);
        $année2025 = new Annee(2025);

        $map = new BalanceAnnuelle()
            ->ajouter($année2024, new Balance(1.))
            ->ajouter($année2025, new Balance(1.))
            ->ajouter($année2024, new Balance(1.));

        self::assertSame(
            [
                $année2025,
                $année2024,
            ],
            $map->getKeys()
        );

        $map = $map->trierParDate();

        self::assertSame(
            [
                $année2024,
                $année2025,
            ],
            $map->getKeys()
        );
    }

    public function test_ajouter_clé_même_unique_key(): void
    {
        $map = new BalanceAnnuelle()
            ->ajouter(
                new Annee(2025),
                new Balance(5.)
            )
            ->ajouter(
                new Annee(2025),
                new Balance(6.)
            );

        self::assertCount(1, $map);

        /** @var Balance $montant */
        $montant = $map->get(new Annee(2025));

        self::assertSame(11., $montant->montant);
    }

    public function test_ajouter_clé_même_objet(): void
    {
        $année = new Annee(2025);

        $map = new BalanceAnnuelle()
            ->ajouter($année, new Balance(5.))
            ->ajouter($année, new Balance(6.));

        self::assertCount(1, $map);

        /** @var Balance $montant */
        $montant = $map->get($année);

        self::assertSame(11., $montant->montant);
    }
}
