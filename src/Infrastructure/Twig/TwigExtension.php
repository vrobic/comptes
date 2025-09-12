<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Compte\Solde;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('solde', [$this, 'solde']),
        ];
    }

    public function solde(int|float $montant): Solde
    {
        return new Solde((float) $montant);
    }
}
