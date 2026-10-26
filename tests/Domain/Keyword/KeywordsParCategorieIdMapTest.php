<?php

declare(strict_types=1);

namespace App\Tests\Domain\Keyword;

use App\Domain\Categorie\CategorieId;
use App\Domain\Keyword\KeywordCollection;
use App\Domain\Keyword\KeywordsParCategorieIdMap;
use App\Tests\GenererInstance;
use PHPUnit\Framework\TestCase;

final class KeywordsParCategorieIdMapTest extends TestCase
{
    public function test_clé_comparée_par_valeur(): void
    {
        $keywords = new KeywordCollection()->add(GenererInstance::keyword());

        $map = new KeywordsParCategorieIdMap()->add(
            new CategorieId('d0c60a73-b561-4842-b41e-09de23b37680'),
            $keywords
        );

        // Une autre instance du même identifiant désigne la même clé
        $mêmeCategorieId = new CategorieId('d0c60a73-b561-4842-b41e-09de23b37680');

        self::assertTrue($map->has($mêmeCategorieId));
        self::assertSame($keywords, $map->get($mêmeCategorieId));
    }

    public function test_to_associative_array(): void
    {
        $map = new KeywordsParCategorieIdMap()
            ->add(
                new CategorieId('d0c60a73-b561-4842-b41e-09de23b37680'),
                new KeywordCollection()->add(
                    $decathlon = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b01', word: 'DECATHLON'),
                    $intersport = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b04', word: 'INTERSPORT'),
                )
            )
            ->add(
                new CategorieId('6c1f0b8e-2d4a-4e7b-9f3c-8a5d2e1b7c40'),
                new KeywordCollection()->add(
                    $sncf = GenererInstance::keyword(id: 'a2f3f6f0-6f0e-4f43-8f0c-0c1d6f7e5b03', word: 'SNCF'),
                )
            );

        self::assertSame(
            [
                'd0c60a73-b561-4842-b41e-09de23b37680' => [$decathlon, $intersport],
                '6c1f0b8e-2d4a-4e7b-9f3c-8a5d2e1b7c40' => [$sncf],
            ],
            $map->toAssociativeArray()
        );
    }

    public function test_to_associative_array_sans_mot_clé(): void
    {
        self::assertSame([], new KeywordsParCategorieIdMap()->toAssociativeArray());
    }
}
