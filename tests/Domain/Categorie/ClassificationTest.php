<?php

declare(strict_types=1);

namespace App\Tests\Domain\Categorie;

use App\Domain\Categorie\CategorieCollection;
use App\Domain\Categorie\Classification;
use App\Tests\GenererInstance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClassificationTest extends TestCase
{
    #[DataProvider('classifications')]
    public function test_déterminer(int $nombreDeCatégories, bool $doublon, Classification $classification): void
    {
        $categories = new CategorieCollection();

        for ($i = 0; $i < $nombreDeCatégories; ++$i) {
            $categories = $categories->add(GenererInstance::catégorie(id: sprintf('d0c60a73-b561-4842-b41e-09de23b3768%d', $i)));
        }

        self::assertSame($classification, Classification::déterminer($categories, $doublon));
    }

    /** @return array{int, bool, Classification}[] */
    public static function classifications(): array
    {
        return [
            'aucune catégorie' => [0, false, Classification::UNCATEGORIZED],
            'une catégorie' => [1, false, Classification::CATEGORIZED],
            'deux catégories' => [2, false, Classification::AMBIGUOUS],
            'trois catégories' => [3, false, Classification::AMBIGUOUS],
            'doublon sans catégorie' => [0, true, Classification::WAITING],
            "doublon d'une catégorie" => [1, true, Classification::WAITING],
            'doublon de plusieurs catégories' => [2, true, Classification::WAITING],
        ];
    }
}
