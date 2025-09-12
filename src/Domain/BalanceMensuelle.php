<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\DataStructure\Map;
use App\Domain\Mouvement\Balance;
use App\Domain\Temps\Mois;

final class BalanceMensuelle extends Map
{
    public function __construct()
    {
        parent::__construct(Mois::class, Balance::class);
    }

    /** @param Mois $key */
    public function getUniqueKey(mixed $key): string
    {
        return (string) $key;
    }

    public function trierParDate(): self
    {
        $entries = [];

        foreach ($this as $mois => $balance) {
            $entries[] = [$mois, $balance];
        }

        usort(
            $entries,
            static function (array $entryA, array $entryB): int {
                /** @var Mois $moisA */
                $moisA = $entryA[0];
                /** @var Mois $moisB */
                $moisB = $entryB[0];

                return ($moisA->année <=> $moisB->année)
                    ?: ($moisA->mois <=> $moisB->mois);
            }
        );

        $map = new self();

        foreach ($entries as [$mois, $balance]) {
            $map = $map->add($mois, $balance);
        }

        return $map;
    }

    public function ajouter(Mois $mois, Balance $balance): self
    {
        $map = clone $this;

        if ($map->has($mois)) {
            $balanceDéjàPrésente = $map->get($mois);
            $map = $map->remove($mois);
        } else {
            $balanceDéjàPrésente = Balance::nulle();
        }

        return $map->add(
            $mois,
            $balanceDéjàPrésente->additionner($balance)
        );
    }

    public function moyenne(): Balance
    {
        $count = $this->count();

        return $count > 0 ? $this->total()->diviser($count) : Balance::nulle();
    }

    public function moyenneDesMoisPositifs(): Balance
    {
        $positifs = $this->filterByValue(
            static fn (Balance $balance): bool => $balance->estPositive()
        );

        $count = $positifs->count();

        return $count > 0 ? $positifs->total()->diviser($count) : Balance::nulle();
    }

    public function total(): Balance
    {
        return $this->reduce(
            static fn (Balance $total, Mois $mois, Balance $balance): Balance => $total->additionner($balance),
            Balance::nulle()
        );
    }
}
