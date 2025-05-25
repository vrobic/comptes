<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\DataStructure\Set;

final class KeywordCollection extends Set
{
    public function __construct()
    {
        parent::__construct(Keyword::class);
    }
}
