<?php

namespace App\Tests\Domain\Mouvement;

use App\Domain\Categorie\CategorieId;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Temps\Annee;
use App\Domain\Temps\Mois;
use App\Domain\Temps\Periode;
use App\Tests\GenererInstance;
use PHPUnit\Framework\TestCase;

final class MouvementCollectionTest extends TestCase
{
    public function test_trier_par_date(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                $mouvement1 = new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                $mouvement2 = new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
            );

        self::assertSame(
            [
                $mouvement1,
                $mouvement2,
            ],
            [...$set]
        );

        $set = $set->trierParDate();

        self::assertSame(
            [
                $mouvement2,
                $mouvement1,
            ],
            [...$set]
        );
    }

    public function test_filtrer_par_période(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                $mouvement1 = new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                $mouvement2 = new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(10.),
                    'chocolat',
                ),
                new Mouvement(
                    new MouvementId('efc28937-66aa-431f-b62d-d0bc3f63bd44'),
                    new \DateTimeImmutable('2024-12-25'),
                    null,
                    $compte,
                    new Montant(3.),
                    'encore des bonbons',
                ),
            );

        self::assertSame(
            [
                $mouvement1,
                $mouvement2,
            ],
            [
                ...$set->filtrerParPériode(
                    new Periode(new \DateTimeImmutable('2025-06-01'), new \DateTimeImmutable('2025-09-30'))
                )
            ]
        );
    }

    public function test_filtrer_par_catégorie(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    GenererInstance::catégorie(id: '4938082d-a57b-4940-a65a-3861fb89270b'),
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(10.),
                    'chocolat',
                ),
                $mouvement3 = new Mouvement(
                    new MouvementId('efc28937-66aa-431f-b62d-d0bc3f63bd44'),
                    new \DateTimeImmutable('2024-12-25'),
                    GenererInstance::catégorie(id: '592608a2-aa28-4d96-8615-d0aec2d09467'),
                    $compte,
                    new Montant(3.),
                    'encore des bonbons',
                ),
            );

        self::assertSame(
            [
                $mouvement3,
            ],
            [
                ...$set->filtrerParCatégorie(
                    new CategorieId('592608a2-aa28-4d96-8615-d0aec2d09467')
                )
            ]
        );
    }

    public function test_get_période(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    $fin = new \DateTimeImmutable('2025-01-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    $début = new \DateTimeImmutable('2025-01-01'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
            );

        $période = $set->getPériode();

        self::assertSame($début, $période->début);
        self::assertSame($fin, $période->fin);
    }

    public function test_balance(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(10.),
                    'chocolat',
                ),
                new Mouvement(
                    new MouvementId('efc28937-66aa-431f-b62d-d0bc3f63bd44'),
                    new \DateTimeImmutable('2024-12-25'),
                    null,
                    $compte,
                    new Montant(3.),
                    'encore des bonbons',
                ),
            );

        self::assertSame(18., $set->balance()->montant);
    }

    public function test_balance_annuelle(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(10.),
                    'chocolat',
                ),
                new Mouvement(
                    new MouvementId('efc28937-66aa-431f-b62d-d0bc3f63bd44'),
                    new \DateTimeImmutable('2024-12-25'),
                    null,
                    $compte,
                    new Montant(3.),
                    'encore des bonbons',
                ),
            );

        $map = $set->balanceAnnuelle(
            new Periode(new \DateTimeImmutable('2025-06-01'), new \DateTimeImmutable('2025-09-30'))
        );

        self::assertCount(2, $map);

        foreach ([
            [2024, 3.],
            [2025, 15.],
        ] as [$année, $montant]) {
            $expectedAnnée = new Annee($année);

            self::assertTrue($map->has($expectedAnnée));

            /** @var Montant $actualMontant */
            $actualMontant = $map->get($expectedAnnée);

            self::assertSame($montant, $actualMontant->montant);
        }
    }

    public function test_balance_mensuelle(): void
    {
        $compte = GenererInstance::compte();

        $set = new MouvementCollection()
            ->add(
                new Mouvement(
                    new MouvementId('f8fa50d1-7e6d-456c-a89d-19377f5f2dec'),
                    new \DateTimeImmutable('2025-08-12'),
                    null,
                    $compte,
                    new Montant(5.),
                    'bonbons',
                ),
                new Mouvement(
                    new MouvementId('f9daddb3-51db-4aba-9c15-51ecbd15ba7b'),
                    new \DateTimeImmutable('2025-08-11'),
                    null,
                    $compte,
                    new Montant(10.),
                    'chocolat',
                ),
                new Mouvement(
                    new MouvementId('efc28937-66aa-431f-b62d-d0bc3f63bd44'),
                    new \DateTimeImmutable('2024-12-25'),
                    null,
                    $compte,
                    new Montant(3.),
                    'encore des bonbons',
                ),
            );

        $map = $set->balanceMensuelle(
            new Periode(new \DateTimeImmutable('2025-06-01'), new \DateTimeImmutable('2025-09-30'))
        );

        self::assertCount(10, $map);

        foreach ([
            [2024, 12, 3.],
            [2025, 1, 0.],
            [2025, 2, 0.],
            [2025, 3, 0.],
            [2025, 4, 0.],
            [2025, 5, 0.],
            [2025, 6, 0.],
            [2025, 7, 0.],
            [2025, 8, 15.],
            [2025, 9, 0.],
        ] as [$année, $mois, $montant]) {
            $expectedMois = new Mois($année, $mois);

            self::assertTrue($map->has($expectedMois));

            /** @var Montant $actualMontant */
            $actualMontant = $map->get($expectedMois);

            self::assertSame($montant, $actualMontant->montant);
        }
    }
}
