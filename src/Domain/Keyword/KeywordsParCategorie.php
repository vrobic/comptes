<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\DataStructure\Map;

final class KeywordsParCategorie extends Map
{
    public function __construct()
    {
        parent::__construct('int', KeywordCollection::class);
    }
}
