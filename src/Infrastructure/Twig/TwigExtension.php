<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formater_montant', [$this, 'formaterMontant']),
            new TwigFilter('formater_solde', [$this, 'formaterSolde']),
            new TwigFilter('formater_balance', [$this, 'formaterBalance']),
        ];
    }

    public function formaterMontant(float $montant): string
    {
        $montantFormaté = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY)
            ->formatCurrency($montant, 'EUR');

        if (false === $montantFormaté) {
            throw new \RuntimeException();
        }

        return $montantFormaté;
    }

    public function formaterSolde(float $solde): string
    {
        return $this->formaterMontant($solde);
    }

    public function formaterBalance(float $balance): string
    {
        return ($balance > 0 ? '+' : '').$this->formaterMontant($balance);
    }
}
