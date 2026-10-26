<?php

declare(strict_types=1);

namespace App\Tests\Domain\Keyword;

use App\Domain\Categorie\Categorie;
use App\Domain\Keyword\KeywordCollection;
use App\Tests\GenererInstance;
use PHPUnit\Framework\TestCase;

final class KeywordCollectionTest extends TestCase
{
    public function test_ajouter_dédoublonne_par_identifiant(): void
    {
        $keywords = new KeywordCollection()->add(
            $decathlon = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON'),
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON'),
        );

        self::assertSame([$decathlon], $keywords->toArray());

        // Même mot, mais identifiant différent : deux mots-clés distincts
        $keywords = $keywords->add(
            $autreDecathlon = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b02', word: 'DECATHLON'),
        );

        self::assertSame([$decathlon, $autreDecathlon], $keywords->toArray());
    }

    public function test_trouver_les_catégories_sans_mot_clé(): void
    {
        $categories = new KeywordCollection()->trouverLesCatégories('PAIEMENT CB 1209 DECATHLON');

        self::assertTrue($categories->isEmpty());
    }

    public function test_trouver_les_catégories_sans_mot_clé_présent(): void
    {
        $keywords = new KeywordCollection()->add(
            GenererInstance::keyword(word: 'DECATHLON'),
        );

        $categories = $keywords->trouverLesCatégories('PAIEMENT CB 1209 INTERSPORT');

        self::assertTrue($categories->isEmpty());
    }

    public function test_trouver_les_catégories_une_catégorie(): void
    {
        $sport = self::sport();

        $keywords = new KeywordCollection()->add(
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON', catégorie: $sport),
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b02', word: 'NANTES', catégorie: $sport),
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b03', word: 'SNCF', catégorie: self::transport()),
        );

        // Deux mots-clés de la même catégorie ne la donnent qu'une fois
        $categories = $keywords->trouverLesCatégories('PAIEMENT CB 1209 DECATHLON NANTES');

        self::assertSame([$sport], $categories->toArray());
    }

    public function test_trouver_les_catégories_plusieurs_catégories(): void
    {
        $sport = self::sport();
        $transport = self::transport();

        $keywords = new KeywordCollection()->add(
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON', catégorie: $sport),
            GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b03', word: 'SNCF', catégorie: $transport),
        );

        $categories = $keywords->trouverLesCatégories('PAIEMENT CB 1209 DECATHLON SNCF');

        self::assertSame([$sport, $transport], $categories->toArray());
    }

    public function test_trier_par_catégorie(): void
    {
        $sport = self::sport();
        $transport = self::transport();

        $keywords = new KeywordCollection()->add(
            $decathlon = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON', catégorie: $sport),
            $sncf = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b03', word: 'SNCF', catégorie: $transport),
            $intersport = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b04', word: 'INTERSPORT', catégorie: $sport),
        );

        $map = $keywords->trierParCatégorie();

        self::assertCount(2, $map);
        self::assertSame([$decathlon, $intersport], $map->get($sport->id)->toArray());
        self::assertSame([$sncf], $map->get($transport->id)->toArray());
    }

    public function test_trier_par_catégorie_sans_mot_clé(): void
    {
        self::assertTrue(new KeywordCollection()->trierParCatégorie()->isEmpty());
    }

    private static function sport(): Categorie
    {
        return GenererInstance::catégorie(id: 'd0c60a73-b561-4842-b41e-09de23b37680', nom: 'Sport');
    }

    private static function transport(): Categorie
    {
        return GenererInstance::catégorie(id: '6c1f0b8e-2d4a-4e7b-9f3c-8a5d2e1b7c40', nom: 'Transport');
    }
}
