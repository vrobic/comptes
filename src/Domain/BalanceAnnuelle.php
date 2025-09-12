<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\DataStructure\Map;
use App\Domain\Mouvement\Balance;
use App\Domain\Temps\Annee;

final class BalanceAnnuelle extends Map
{
    public function __construct()
    {
        parent::__construct(Annee::class, Balance::class);
    }

    /** @param Annee $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key;
    }

    public function trierParDate(): self
    {
        $entries = [];

        foreach ($this as $année => $balance) {
            $entries[] = [$année, $balance];
        }

        usort(
            $entries,
            static function (array $entryA, array $entryB): int {
                /** @var Annee $annéeA */
                $annéeA = $entryA[0];
                /** @var Annee $annéeB */
                $annéeB = $entryB[0];

                return $annéeA->année <=> $annéeB->année;
            }
        );

        $map = new self();

        foreach ($entries as [$année, $balance]) {
            $map = $map->add($année, $balance);
        }

        return $map;
    }

    public function ajouter(Annee $année, Balance $balance): self
    {
        $map = clone $this;

        if ($map->has($année)) {
            $balanceDéjàPrésente = $map->get($année);
            $map = $map->remove($année);
        } else {
            $balanceDéjàPrésente = Balance::nulle();
        }

        return $map->add(
            $année,
            $balanceDéjàPrésente->additionner($balance)
        );
    }
}
