<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

use App\Domain\Id\IdInterface;
use App\Infrastructure\Id\Uuid;

final readonly class KeywordId extends Uuid implements IdInterface
{
}
