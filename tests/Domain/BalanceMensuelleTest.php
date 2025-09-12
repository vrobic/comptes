<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\BalanceMensuelle;
use App\Domain\Mouvement\Balance;
use App\Domain\Temps\Mois;
use PHPUnit\Framework\TestCase;

final class BalanceMensuelleTest extends TestCase
{
    public function test_trier_par_date(): void
    {
        $moisJuin2025 = new Mois(2025, 6);
        $moisNovembre2024 = new Mois(2024, 11);

        $map = new BalanceMensuelle()
            ->ajouter($moisNovembre2024, new Balance(1.))
            ->ajouter($moisJuin2025, new Balance(1.))
            ->ajouter($moisNovembre2024, new Balance(1.));

        self::assertSame(
            [
                $moisJuin2025,
                $moisNovembre2024,
            ],
            $map->getKeys()
        );

        $map = $map->trierParDate();

        self::assertSame(
            [
                $moisNovembre2024,
                $moisJuin2025,
            ],
            $map->getKeys()
        );
    }

    public function test_ajouter_clé_même_unique_key(): void
    {
        $map = new BalanceMensuelle()
            ->ajouter(
                new Mois(2025, 3),
                new Balance(5.)
            )
            ->ajouter(
                new Mois(2025, 3),
                new Balance(6.)
            );

        self::assertCount(1, $map);

        /** @var Balance $montant */
        $montant = $map->get(new Mois(2025, 3));

        self::assertSame(11., $montant->montant);
    }

    public function test_ajouter_clé_même_objet(): void
    {
        $mois = new Mois(2025, 8);

        $map = new BalanceMensuelle()
            ->ajouter($mois, new Balance(5.))
            ->ajouter($mois, new Balance(6.));

        self::assertCount(1, $map);

        /** @var Balance $montant */
        $montant = $map->get($mois);

        self::assertSame(11., $montant->montant);
    }

    public function test_moyenne(): void
    {
        self::assertSame(
            0.,
            new BalanceMensuelle()
                ->ajouter(new Mois(2010, 1), new Balance(-10.))
                ->ajouter(new Mois(2025, 3), new Balance(10.))
                ->moyenne()
                ->montant
        );
    }

    public function test_moyenne_des_mois_positifs(): void
    {
        self::assertSame(
            10.,
            new BalanceMensuelle()
                ->ajouter(new Mois(2010, 1), new Balance(-10.))
                ->ajouter(new Mois(2025, 3), new Balance(10.))
                ->moyenneDesMoisPositifs()
                ->montant
        );
    }

    public function test_total(): void
    {
        self::assertSame(
            20.,
            new BalanceMensuelle()
                ->ajouter(new Mois(2010, 1), new Balance(10.))
                ->ajouter(new Mois(2025, 3), new Balance(10.))
                ->total()
                ->montant
        );
    }
}
