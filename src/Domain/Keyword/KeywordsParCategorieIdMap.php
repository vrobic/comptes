<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\DataStructure\Map;

final class KeywordsParCategorieIdMap extends Map
{
    public function __construct()
    {
        parent::__construct(
            'string', // Le map ne supporte pas bien d'avoir des objets comme clés
            KeywordCollection::class
        );
    }
}
