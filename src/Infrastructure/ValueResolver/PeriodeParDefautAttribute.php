<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueResolver;

use App\Domain\Temps\Depuis;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class PeriodeParDefautAttribute
{
    public function __construct(public Depuis $depuis)
    {
    }
}
